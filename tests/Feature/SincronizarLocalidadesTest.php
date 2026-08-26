<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\ItemAcervo;
use App\Models\MotivoDownload;
use App\Models\Municipio;
use App\Models\Pais;
use App\Models\RegistroDownload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SincronizarLocalidadesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Respostas atuais do IBGE, indexadas por trecho da URL. Os testes trocam
     * este mapa entre sincronizações para simular mudanças e falhas.
     *
     * @var array<string, array{mixed, int}>
     */
    private array $respostas = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Nenhum teste pode tocar a rede: qualquer chamada não fingida falha.
        Http::preventStrayRequests();
        config(['services.ibge.intervalo_retry' => 0]);

        Http::fake(function ($request) {
            foreach ($this->respostas as $trecho => [$corpo, $status]) {
                if (str_contains($request->url(), $trecho)) {
                    return Http::response($corpo, $status);
                }
            }

            return Http::response('rota nao mapeada no teste', 404);
        });
    }

    /**
     * @param  array<string, array{mixed, int}>  $respostas
     */
    private function responder(array $respostas): void
    {
        $this->respostas = $respostas;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paisesFake(): array
    {
        return [
            // Formato atual da API: `nome` como string.
            ['id' => ['M49' => 76, 'ISO-ALPHA-2' => 'BR', 'ISO-ALPHA-3' => 'BRA'], 'nome' => 'Brasil'],
            // Formato alternativo (`nome` como objeto), também aceito pelo comando.
            ['id' => ['M49' => 620, 'ISO-ALPHA-2' => 'PT', 'ISO-ALPHA-3' => 'PRT'], 'nome' => ['abreviado' => 'Portugal']],
            // Registro incompleto: deve ser ignorado, não quebrar a sincronização.
            ['id' => ['M49' => 999], 'nome' => 'Pais sem ISO'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function estadosFake(): array
    {
        return [
            ['id' => 41, 'sigla' => 'PR', 'nome' => 'Parana'],
            ['id' => 35, 'sigla' => 'SP', 'nome' => 'Sao Paulo'],
        ];
    }

    private function respostasPadrao(): void
    {
        $this->responder([
            '/paises' => [$this->paisesFake(), 200],
            '/estados/PR/municipios' => [[
                ['id' => 4128104, 'nome' => 'Umuarama'],
                ['id' => 4106902, 'nome' => 'Curitiba'],
            ], 200],
            '/estados/SP/municipios' => [[['id' => 3550308, 'nome' => 'Sao Paulo']], 200],
            '/estados' => [$this->estadosFake(), 200],
        ]);
    }

    public function test_sincroniza_paises_estados_e_municipios(): void
    {
        $this->respostasPadrao();

        $this->artisan('localidades:sync')->assertSuccessful();

        $this->assertSame(2, Pais::count());
        $this->assertSame(2, Estado::count());
        $this->assertSame(3, Municipio::count());

        $this->assertSame('Brasil', Pais::where('codigo', 'BR')->value('nome'));
        $this->assertSame('41', Estado::where('sigla', 'PR')->value('codigo_ibge'));
        $this->assertSame('4128104', Municipio::where('nome', 'Umuarama')->value('codigo_ibge'));
        $this->assertSame('PR', Municipio::where('nome', 'Umuarama')->first()->estado->sigla);
    }

    public function test_execucoes_repetidas_nao_duplicam_registros(): void
    {
        $this->respostasPadrao();

        $this->artisan('localidades:sync')->assertSuccessful();
        $this->artisan('localidades:sync')->assertSuccessful();
        $this->artisan('localidades:sync')->assertSuccessful();

        $this->assertSame(2, Pais::count());
        $this->assertSame(2, Estado::count());
        $this->assertSame(3, Municipio::count());
    }

    public function test_atualiza_nomes_alterados_sem_criar_novos_registros(): void
    {
        $this->respostasPadrao();
        $this->artisan('localidades:sync')->assertSuccessful();

        $idMunicipio = Municipio::where('codigo_ibge', '4128104')->value('id');

        $this->responder([
            '/paises' => [[['id' => ['ISO-ALPHA-2' => 'BR'], 'nome' => 'Brasil (renomeado)']], 200],
            '/estados/PR/municipios' => [[['id' => 4128104, 'nome' => 'Umuarama (renomeado)']], 200],
            '/estados/SP/municipios' => [[], 200],
            '/estados' => [$this->estadosFake(), 200],
        ]);

        $this->artisan('localidades:sync')->assertSuccessful();

        $this->assertSame('Brasil (renomeado)', Pais::where('codigo', 'BR')->value('nome'));
        $this->assertSame('Umuarama (renomeado)', Municipio::where('codigo_ibge', '4128104')->value('nome'));
        $this->assertSame($idMunicipio, Municipio::where('codigo_ibge', '4128104')->value('id'));
    }

    public function test_nao_remove_municipios_ausentes_na_resposta(): void
    {
        $this->respostasPadrao();
        $this->artisan('localidades:sync')->assertSuccessful();

        $this->responder([
            '/paises' => [$this->paisesFake(), 200],
            '/estados/PR/municipios' => [[['id' => 4106902, 'nome' => 'Curitiba']], 200],
            '/estados/SP/municipios' => [[], 200],
            '/estados' => [$this->estadosFake(), 200],
        ]);

        $this->artisan('localidades:sync')->assertSuccessful();

        $this->assertNotNull(Municipio::where('codigo_ibge', '4128104')->first());
        $this->assertSame(3, Municipio::count());
    }

    public function test_preserva_a_flag_ativo_definida_pela_aplicacao(): void
    {
        Pais::create(['codigo' => 'PT', 'nome' => 'Portugal', 'ativo' => false]);

        $this->respostasPadrao();
        $this->artisan('localidades:sync')->assertSuccessful();

        $this->assertFalse(Pais::where('codigo', 'PT')->first()->ativo);
    }

    public function test_falha_do_ibge_nao_derruba_o_comando_nem_a_base(): void
    {
        $this->respostasPadrao();
        $this->artisan('localidades:sync')->assertSuccessful();

        $this->responder(['/' => ['indisponivel', 503]]);

        $this->artisan('localidades:sync')->assertFailed();

        // A base local continua íntegra: os selects seguem funcionando.
        $this->assertSame(2, Pais::count());
        $this->assertSame(2, Estado::count());
        $this->assertSame(3, Municipio::count());
    }

    public function test_falha_em_uma_uf_nao_impede_as_demais(): void
    {
        $this->responder([
            '/paises' => [$this->paisesFake(), 200],
            '/estados/PR/municipios' => ['erro interno', 500],
            '/estados/SP/municipios' => [[['id' => 3550308, 'nome' => 'Sao Paulo']], 200],
            '/estados' => [$this->estadosFake(), 200],
        ]);

        $this->artisan('localidades:sync')->assertFailed();

        $this->assertSame(1, Municipio::count());
        $this->assertSame('Sao Paulo', Municipio::first()->nome);
    }

    public function test_opcao_uf_limita_os_municipios_sincronizados(): void
    {
        $this->respostasPadrao();
        $this->artisan('localidades:sync --estados')->assertSuccessful();

        $this->artisan('localidades:sync --municipios --uf=PR')->assertSuccessful();

        $this->assertSame(2, Municipio::count());
        $this->assertSame(0, Municipio::whereHas('estado', fn ($q) => $q->where('sigla', 'SP'))->count());
    }

    public function test_municipios_sem_ufs_na_base_reporta_falha(): void
    {
        $this->respostasPadrao();

        $this->artisan('localidades:sync --municipios')->assertFailed();

        $this->assertSame(0, Municipio::count());
    }

    public function test_localidade_referenciada_continua_disponivel_apos_sync(): void
    {
        $this->respostasPadrao();
        $this->artisan('localidades:sync')->assertSuccessful();

        $item = ItemAcervo::create([
            'titulo' => 'Praca central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'publicado',
        ]);

        $registro = RegistroDownload::create([
            'item_acervo_id' => $item->id,
            'motivo_download_id' => MotivoDownload::create(['titulo' => 'Pesquisa'])->id,
            'pais_id' => Pais::where('codigo', 'BR')->value('id'),
            'estado_id' => Estado::where('sigla', 'PR')->value('id'),
            'municipio_id' => Municipio::where('codigo_ibge', '4128104')->value('id'),
            'created_at' => now(),
        ]);

        $this->artisan('localidades:sync')->assertSuccessful();

        $registro->refresh();

        $this->assertSame('Umuarama', $registro->municipio->nome);
    }
}
