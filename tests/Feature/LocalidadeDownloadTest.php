<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\ItemAcervo;
use App\Models\MotivoDownload;
use App\Models\Municipio;
use App\Models\Pais;
use App\Models\PerfilDownload;
use App\Models\RegistroDownload;
use App\Models\User;
use App\Queries\Admin\EstatisticasDownloads;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LocalidadeDownloadTest extends TestCase
{
    use RefreshDatabase;

    private Pais $brasil;

    private Pais $portugal;

    private Estado $parana;

    private Estado $saoPaulo;

    private Municipio $umuarama;

    private Municipio $campinas;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->portugal = Pais::create(['codigo' => 'PT', 'nome' => 'Portugal']);
        $this->brasil = Pais::create(['codigo' => 'BR', 'nome' => 'Brasil']);
        Pais::create(['codigo' => 'AR', 'nome' => 'Argentina']);
        Pais::create(['codigo' => 'XX', 'nome' => 'País inativo', 'ativo' => false]);
        $this->saoPaulo = Estado::create(['codigo_ibge' => '35', 'sigla' => 'SP', 'nome' => 'São Paulo']);
        $this->parana = Estado::create(['codigo_ibge' => '41', 'sigla' => 'PR', 'nome' => 'Paraná']);
        $this->umuarama = Municipio::create(['codigo_ibge' => '4128104', 'estado_id' => $this->parana->id, 'nome' => 'Umuarama']);
        Municipio::create(['codigo_ibge' => '4106902', 'estado_id' => $this->parana->id, 'nome' => 'Curitiba']);
        $this->campinas = Municipio::create(['codigo_ibge' => '3509502', 'estado_id' => $this->saoPaulo->id, 'nome' => 'Campinas']);
    }

    public function test_form_lists_active_countries_with_brazil_first_and_states(): void
    {
        $xpath = $this->dom($this->get(route('public.fotografias.download.create', $this->fotografia()))->assertOk());

        $this->assertSame(['Selecione', 'Brasil', 'Argentina', 'Portugal'], $this->opcoes($xpath, 'download-country'));
        $this->assertSame(['Selecione', 'Paraná', 'São Paulo'], $this->opcoes($xpath, 'download-state'));
        $this->assertSame('pais_id', $xpath->query('//select[@id="download-country"]')->item(0)->getAttribute('name'));
        $this->assertTrue($xpath->query('//select[@id="download-country"]')->item(0)->hasAttribute('required'));
        $this->assertStringContainsString('localidade', $xpath->query('//form')->item(0)->textContent);
    }

    public function test_brazil_requires_state_and_city_and_records_all_three(): void
    {
        $fotografia = $this->fotografia();

        $this->from(route('public.fotografias.download.create', $fotografia))
            ->post(route('public.fotografias.download.store', $fotografia), $this->dados(['pais_id' => $this->brasil->id]))
            ->assertRedirect(route('public.fotografias.download.create', $fotografia))
            ->assertSessionHasErrors(['estado_id' => 'O campo estado é obrigatório.', 'municipio_id' => 'O campo cidade é obrigatório.']);
        $this->assertDatabaseCount('registro_downloads', 0);

        $this->post(route('public.fotografias.download.store', $fotografia), $this->dados([
            'pais_id' => $this->brasil->id,
            'estado_id' => $this->parana->id,
            'municipio_id' => $this->umuarama->id,
        ]))->assertOk()->assertDownload();

        $registro = RegistroDownload::query()->sole();
        $this->assertSame([$this->brasil->id, $this->parana->id, $this->umuarama->id], [$registro->pais_id, $registro->estado_id, $registro->municipio_id]);
    }

    public function test_city_must_belong_to_selected_state(): void
    {
        $fotografia = $this->fotografia();

        $this->from(route('public.fotografias.download.create', $fotografia))
            ->post(route('public.fotografias.download.store', $fotografia), $this->dados([
                'pais_id' => $this->brasil->id,
                'estado_id' => $this->parana->id,
                'municipio_id' => $this->campinas->id,
            ]))
            ->assertSessionHasErrors(['municipio_id' => 'Selecione uma cidade do estado escolhido.']);
        $this->assertDatabaseCount('registro_downloads', 0);
    }

    public function test_foreign_country_ignores_state_and_city(): void
    {
        $fotografia = $this->fotografia();

        $this->post(route('public.fotografias.download.store', $fotografia), $this->dados([
            'pais_id' => $this->portugal->id,
            'estado_id' => $this->parana->id,
            'municipio_id' => $this->campinas->id,
        ]))->assertOk()->assertDownload();

        $registro = RegistroDownload::query()->sole();
        $this->assertSame($this->portugal->id, $registro->pais_id);
        $this->assertNull($registro->estado_id);
        $this->assertNull($registro->municipio_id);
    }

    public function test_country_is_required_active_and_existing(): void
    {
        $fotografia = $this->fotografia();
        $inativo = Pais::where('codigo', 'XX')->value('id');

        foreach ([null, $inativo, 999999] as $pais) {
            $this->from(route('public.fotografias.download.create', $fotografia))
                ->post(route('public.fotografias.download.store', $fotografia), $this->dados(['pais_id' => $pais]))
                ->assertSessionHasErrors('pais_id');
        }
        $this->assertDatabaseCount('registro_downloads', 0);
    }

    public function test_validation_error_restores_location_with_city_list(): void
    {
        $fotografia = $this->fotografia();

        $resposta = $this->from(route('public.fotografias.download.create', $fotografia))->followingRedirects()
            ->post(route('public.fotografias.download.store', $fotografia), $this->dados([
                'email' => 'invalido',
                'pais_id' => $this->brasil->id,
                'estado_id' => $this->parana->id,
                'municipio_id' => $this->umuarama->id,
            ]))
            ->assertOk();
        $xpath = $this->dom($resposta);

        $this->assertSame((string) $this->brasil->id, $xpath->query('//select[@id="download-country"]/option[@selected]')->item(0)?->getAttribute('value'));
        $this->assertSame((string) $this->parana->id, $xpath->query('//select[@id="download-state"]/option[@selected]')->item(0)?->getAttribute('value'));
        // As cidades do estado e a cidade escolhida voltam no estado inicial do Alpine.
        // @js gera JSON.parse('…') com as aspas escapadas.
        $config = str_replace('\\u0022', '"', $xpath->query('//fieldset[@x-data]')->item(0)->getAttribute('x-data'));
        $this->assertStringContainsString('Umuarama', $config);
        $this->assertStringContainsString('Curitiba', $config);
        $this->assertStringNotContainsString('Campinas', $config);
        $this->assertMatchesRegularExpression('/"municipio":"?'.$this->umuarama->id.'"?[,}]/', $config);
    }

    public function test_cities_endpoint_lists_state_cities_in_order(): void
    {
        $this->getJson(route('public.localidades.municipios', $this->parana))
            ->assertOk()
            ->assertExactJson([
                ['id' => Municipio::where('nome', 'Curitiba')->value('id'), 'nome' => 'Curitiba'],
                ['id' => $this->umuarama->id, 'nome' => 'Umuarama'],
            ]);
        $this->getJson(route('public.localidades.municipios', ['estado' => 999999]))->assertNotFound();
    }

    public function test_report_groups_downloads_by_origin_and_profile(): void
    {
        $perfis = PerfilDownload::query()->orderBy('id')->limit(2)->get();
        $this->registro($this->brasil, $this->parana, $this->umuarama, $perfis[0]);
        $this->registro($this->brasil, $this->parana, $this->umuarama, $perfis[1]);
        $this->registro($this->brasil, $this->saoPaulo, $this->campinas, $perfis[0]);
        $this->registro($this->portugal, null, null, $perfis[0]);
        $this->registro(null, null, null, $perfis[1]);

        $estatisticas = app(EstatisticasDownloads::class);
        $consulta = $estatisticas->consulta();
        $resumo = fn ($grupos): array => $grupos->map(fn (object $grupo): array => [$grupo->rotulo, $grupo->total])->all();

        $this->assertSame([['Brasil', 3], ['Não informado', 1], ['Portugal', 1]], $resumo($estatisticas->paises($consulta)));
        $this->assertSame([['Paraná (PR)', 2], ['São Paulo (SP)', 1]], $resumo($estatisticas->estados($consulta)));
        $this->assertSame([['Umuarama/PR', 2], ['Campinas/SP', 1]], $resumo($estatisticas->municipios($consulta)));

        $porPais = $estatisticas->perfisPorPais($consulta);
        $this->assertSame('Brasil', $porPais[0]->rotulo);
        $this->assertSame([[$perfis[0]->titulo, 2], [$perfis[1]->titulo, 1]], $resumo($porPais[0]->perfis));

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
        $this->actingAs($admin)->get(route('admin.downloads.index'))
            ->assertOk()
            ->assertSee('Origem dos solicitantes')
            ->assertSee('Umuarama/PR')
            ->assertSee('Paraná (PR)')
            ->assertSee('Perfis por país');
        $this->actingAs($admin)->get(route('admin.downloads.index', ['aba' => 'registros']))
            ->assertOk()
            ->assertSee('Umuarama/PR')
            ->assertSee('Portugal')
            ->assertSee('Origem não informada');
    }

    public function test_csv_exports_location_columns(): void
    {
        $this->registro($this->brasil, $this->parana, $this->umuarama, PerfilDownload::query()->first());
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);

        $resposta = $this->actingAs($admin)->get(route('admin.downloads.export'))->assertOk();
        ob_start();
        $resposta->baseResponse->sendContent();
        $linhas = array_map(fn (string $linha) => str_getcsv($linha, ';', '"', ''), explode("\n", trim(substr((string) ob_get_clean(), 3))));

        $this->assertSame(['País', 'Estado', 'Município'], array_slice($linhas[0], -3));
        $this->assertSame(['Brasil', 'PR', 'Umuarama'], array_slice($linhas[1], -3));
    }

    private function fotografia(): ItemAcervo
    {
        $fotografia = ItemAcervo::create(['titulo' => 'Praça central', 'tipo_item' => 'fotografia', 'status' => 'publicado', 'visibilidade' => 'publico']);
        $large = $fotografia->arquivos()->create([
            'provider' => 'local',
            'storage_path' => "acervo/{$fotografia->id}/large.jpg",
            'mime_type' => 'image/jpeg',
            'file_size' => 100,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'large',
        ]);
        Storage::disk('local')->put($large->storage_path, 'conteudo');

        return $fotografia;
    }

    private function dados(array $localidade): array
    {
        return array_merge([
            'nome' => 'Ana Silva',
            'email' => 'ana@example.com',
            'perfil_download_id' => PerfilDownload::query()->value('id'),
            'motivo_download_id' => MotivoDownload::query()->value('id'),
            'finalidade' => 'Pesquisa sobre a história de Umuarama',
            'aceite' => '1',
        ], $localidade);
    }

    private function registro(?Pais $pais, ?Estado $estado, ?Municipio $municipio, PerfilDownload $perfil): void
    {
        RegistroDownload::create([
            'item_acervo_id' => null,
            'titulo_item' => 'Praça central',
            'perfil_download_id' => $perfil->id,
            'motivo_download_id' => MotivoDownload::query()->value('id'),
            'pais_id' => $pais?->id,
            'estado_id' => $estado?->id,
            'municipio_id' => $municipio?->id,
            'nome' => 'Ana Silva',
            'email' => 'ana@example.com',
            'finalidade' => 'Pesquisa',
            'termos_aceitos_em' => now(),
            'created_at' => now(),
        ]);
    }

    /** @return list<string> */
    private function opcoes(DOMXPath $xpath, string $id): array
    {
        return array_map(fn ($opcao): string => trim($opcao->textContent), iterator_to_array($xpath->query(sprintf('//select[@id="%s"]/option', $id))));
    }

    private function dom(TestResponse $resposta): DOMXPath
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$resposta->getContent(), LIBXML_NOERROR);

        return new DOMXPath($dom);
    }
}
