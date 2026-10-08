<?php

namespace Tests\Feature\Publico;

use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ImagemPublicaTest extends TestCase
{
    use RefreshDatabase;

    private const CONTEUDO = 'conteudo-da-imagem-publica';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_visitante_visualiza_derivacoes_autorizadas_com_headers_seguros(): void
    {
        foreach (['thumbnail', 'medium', 'large'] as $versao) {
            $fotografia = $this->fotografia();
            $arquivo = $this->arquivo($fotografia, $versao);
            Storage::disk('local')->put($arquivo->storage_path, self::CONTEUDO);

            $response = $this->get(route('publico.imagens.show', $arquivo))
                ->assertOk()
                ->assertHeader('Content-Type', 'image/jpeg')
                ->assertHeader('Content-Disposition', 'inline')
                ->assertHeader('X-Content-Type-Options', 'nosniff');

            $cacheControl = (string) $response->headers->get('Cache-Control');

            $this->assertStringContainsString('private', $cacheControl);
            $this->assertStringContainsString('no-store', $cacheControl);
            $this->assertStringNotContainsString('public', $cacheControl);
            $this->assertSame(self::CONTEUDO, $this->conteudoBinario($response));
        }
    }

    public function test_usuario_autenticado_utiliza_o_mesmo_endpoint_publico(): void
    {
        $fotografia = $this->fotografia();
        $arquivo = $this->arquivo($fotografia, 'thumbnail');
        Storage::disk('local')->put($arquivo->storage_path, self::CONTEUDO);

        $this->actingAs(User::factory()->create())
            ->get(route('publico.imagens.show', $arquivo))
            ->assertOk();
    }

    public function test_original_continua_bloqueado_mesmo_quando_fotografia_tem_derivacao_valida(): void
    {
        $fotografia = $this->fotografia();
        $derivacao = $this->arquivo($fotografia, 'thumbnail');
        $original = $this->arquivo($fotografia, 'original', [
            'nome_original' => 'original.jpg',
        ]);
        Storage::disk('local')->put($derivacao->storage_path, self::CONTEUDO);
        Storage::disk('local')->put($original->storage_path, 'conteudo-original-privado');

        $this->get(route('publico.imagens.show', $original))
            ->assertNotFound()
            ->assertDontSee('conteudo-original-privado');
    }

    public function test_documento_e_metadados_incoerentes_nao_sao_expostos(): void
    {
        $casos = [
            ['mime_type' => 'application/pdf', 'tipo_arquivo' => 'documento'],
            ['mime_type' => 'image/jpeg', 'tipo_arquivo' => 'documento'],
            ['mime_type' => 'application/octet-stream', 'tipo_arquivo' => 'imagem'],
        ];

        foreach ($casos as $atributos) {
            $fotografia = $this->fotografia();
            $arquivo = $this->arquivo($fotografia, 'thumbnail', $atributos);
            Storage::disk('local')->put($arquivo->storage_path, 'conteudo-indevido');

            $this->get(route('publico.imagens.show', $arquivo))
                ->assertNotFound()
                ->assertDontSee('conteudo-indevido');
        }
    }

    public function test_fotografias_fora_das_regras_publicas_nao_expoem_derivacoes(): void
    {
        $casos = [
            ['status' => 'rascunho'],
            ['status' => 'em_revisao'],
            ['status' => 'arquivado'],
            ['visibilidade' => Visibilidade::Privado],
            ['tipo_item' => 'documento'],
        ];

        foreach ($casos as $atributos) {
            $fotografia = $this->fotografia($atributos);
            $arquivo = $this->arquivo($fotografia, 'thumbnail');
            Storage::disk('local')->put($arquivo->storage_path, self::CONTEUDO);

            $this->get(route('publico.imagens.show', $arquivo))->assertNotFound();
        }
    }

    public function test_fotografia_removida_logicamente_nao_expoe_derivacao(): void
    {
        $fotografia = $this->fotografia();
        $arquivo = $this->arquivo($fotografia, 'thumbnail');
        Storage::disk('local')->put($arquivo->storage_path, self::CONTEUDO);
        $fotografia->delete();

        $this->get(route('publico.imagens.show', $arquivo))->assertNotFound();
    }

    public function test_imagem_publica_deixa_de_ser_acessivel_apos_revogacao(): void
    {
        $revogacoes = [
            fn (ItemAcervo $fotografia) => $fotografia->update(['status' => 'arquivado']),
            fn (ItemAcervo $fotografia) => $fotografia->update(['visibilidade' => Visibilidade::Privado]),
            fn (ItemAcervo $fotografia) => $fotografia->delete(),
        ];

        foreach ($revogacoes as $revogar) {
            $fotografia = $this->fotografia();
            $arquivo = $this->arquivo($fotografia, 'thumbnail');
            Storage::disk('local')->put($arquivo->storage_path, self::CONTEUDO);

            $this->get(route('publico.imagens.show', $arquivo))->assertOk();

            $revogar($fotografia);

            $this->get(route('publico.imagens.show', $arquivo))->assertNotFound();
        }
    }

    public function test_provider_nao_suportado_nao_e_elegivel_nem_exposto(): void
    {
        $fotografia = $this->fotografia();
        $arquivo = $this->arquivo($fotografia, 'thumbnail', ['provider' => 's3']);

        $this->assertNull(app(ConsultaFotografiasPublicas::class)
            ->porIdentificador($fotografia->id));
        $this->get(route('publico.imagens.show', $arquivo))->assertNotFound();
    }

    public function test_registro_e_objeto_fisico_ausentes_retornam_nao_encontrado(): void
    {
        $fotografia = $this->fotografia();
        $arquivo = $this->arquivo($fotografia, 'thumbnail');

        $this->get(route('publico.imagens.show', $arquivo))->assertNotFound();
        $this->get(route('publico.imagens.show', 999999))->assertNotFound();
    }

    public function test_resposta_negada_nao_revela_metadados_privados(): void
    {
        $fotografia = $this->fotografia(['visibilidade' => Visibilidade::Privado]);
        $arquivo = $this->arquivo($fotografia, 'thumbnail', [
            'nome_original' => 'nome-confidencial.jpg',
            'storage_path' => 'acervo/privado/caminho-confidencial.jpg',
        ]);

        $this->get(route('publico.imagens.show', $arquivo))
            ->assertNotFound()
            ->assertDontSee($arquivo->storage_path)
            ->assertDontSee($arquivo->nome_original);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fotografia(array $attributes = []): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => 'Fotografia pública '.uniqid(),
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function arquivo(ItemAcervo $fotografia, string $versao, array $attributes = []): Arquivo
    {
        return Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'nome_original' => null,
            'provider' => 'local',
            'storage_path' => "acervo/derivados/{$fotografia->id}/{$versao}.jpg",
            'mime_type' => 'image/jpeg',
            'file_size' => strlen(self::CONTEUDO),
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => $versao,
            'width' => 800,
            'height' => 600,
            ...$attributes,
        ]);
    }

    private function conteudoBinario(TestResponse $response): string
    {
        ob_start();
        $response->baseResponse->sendContent();

        return (string) ob_get_clean();
    }
}
