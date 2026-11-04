<?php

namespace Tests\Feature\Publico;

use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\Assunto;
use App\Models\Categoria;
use App\Models\ItemAcervo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SegurancaIntegracaoPublicaTest extends TestCase
{
    use RefreshDatabase;

    private const CONTEUDO_DERIVACAO = 'conteudo-da-derivacao-publica';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_visitante_acessa_catalogo_detalhe_e_imagem_elegivel_sem_autenticacao(): void
    {
        $fotografia = $this->fotografia(['titulo' => 'Memória pública integrada']);
        $derivacao = $this->arquivo($fotografia, 'thumbnail');

        $this->get(route('public.catalogo'))
            ->assertOk()
            ->assertSee('Memória pública integrada')
            ->assertSee(route('public.fotografias.show', $fotografia), false)
            ->assertSee(route('public.imagens.show', $derivacao), false);

        $this->get(route('public.fotografias.show', $fotografia))
            ->assertOk()
            ->assertSee('Memória pública integrada')
            ->assertSee(route('public.imagens.show', $derivacao), false);

        $this->get(route('public.imagens.show', $derivacao))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_registros_inelegiveis_ficam_ausentes_de_todas_as_superficies_publicas(): void
    {
        $casos = collect([
            ['titulo' => 'Segurança rascunho', 'status' => 'rascunho'],
            ['titulo' => 'Segurança em revisão', 'status' => 'em_revisao'],
            ['titulo' => 'Segurança arquivada', 'status' => 'arquivado'],
            ['titulo' => 'Segurança privada', 'visibilidade' => Visibilidade::Privado],
        ])->map(function (array $atributos): array {
            $fotografia = $this->fotografia($atributos);

            return [$fotografia, $this->arquivo($fotografia, 'thumbnail')];
        });

        $excluida = $this->fotografia(['titulo' => 'Segurança excluída']);
        $arquivoExcluido = $this->arquivo($excluida, 'thumbnail');
        $excluida->delete();
        $casos->push([$excluida, $arquivoExcluido]);

        $somenteOriginal = $this->fotografia(['titulo' => 'Segurança sem derivação pública']);
        $original = $this->arquivo($somenteOriginal, 'original', [
            'nome_original' => 'registro-original.jpg',
        ]);
        $casos->push([$somenteOriginal, $original]);

        $catalogo = $this->get(route('public.catalogo', ['q' => 'Segurança']))->assertOk();
        $home = $this->get(route('public.home'))->assertOk();

        $casos->each(function (array $caso) use ($catalogo, $home): void {
            [$fotografia, $arquivo] = $caso;

            $catalogo->assertDontSee($fotografia->titulo);
            $home->assertDontSee($fotografia->titulo);
            $this->get(route('public.fotografias.show', $fotografia->id))->assertNotFound();
            $this->get(route('public.imagens.show', $arquivo))->assertNotFound();
        });
    }

    public function test_original_e_seus_metadados_nao_sao_expostos_pelas_paginas_publicas(): void
    {
        $fotografia = $this->fotografia(['titulo' => 'Fotografia com original protegido']);
        $derivacao = $this->arquivo($fotografia, 'large');
        $original = $this->arquivo($fotografia, 'original', [
            'nome_original' => 'nome-original-confidencial.tiff',
            'storage_path' => "acervo/originais/{$fotografia->id}/caminho-confidencial.tiff",
            'mime_type' => 'image/tiff',
        ]);

        foreach ([route('public.catalogo'), route('public.fotografias.show', $fotografia)] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee(route('public.imagens.show', $derivacao), false)
                ->assertDontSee(route('public.imagens.show', $original), false)
                ->assertDontSee(route('admin.arquivos.show', $original), false)
                ->assertDontSee($original->nome_original)
                ->assertDontSee($original->storage_path);
        }

        $this->get(route('public.imagens.show', $original))
            ->assertNotFound()
            ->assertDontSee($original->nome_original)
            ->assertDontSee($original->storage_path)
            ->assertDontSee('conteudo-original');
    }

    public function test_revogacao_remove_imediatamente_a_fotografia_de_todas_as_superficies_publicas(): void
    {
        $revogacoes = [
            'alteração de status' => fn (ItemAcervo $fotografia) => $fotografia->update(['status' => 'rascunho']),
            'alteração de visibilidade' => fn (ItemAcervo $fotografia) => $fotografia->update(['visibilidade' => Visibilidade::Privado]),
            'exclusão lógica' => fn (ItemAcervo $fotografia) => $fotografia->delete(),
        ];

        foreach ($revogacoes as $identificador => $revogar) {
            $titulo = 'Revogação pública por '.$identificador;
            $fotografia = $this->fotografia(['titulo' => $titulo]);
            $derivacao = $this->arquivo($fotografia, 'medium');

            $this->assertFotografiaPublicamenteDisponivel($fotografia, $derivacao);

            $revogar($fotografia);

            $this->get(route('public.home'))->assertOk()->assertDontSee($titulo);
            $this->get(route('public.catalogo', ['q' => $titulo]))
                ->assertOk()
                ->assertViewHas('fotografias', fn ($paginador): bool => $paginador->isEmpty())
                ->assertDontSee(route('public.fotografias.show', $fotografia->id), false);
            $this->get(route('public.fotografias.show', $fotografia->id))->assertNotFound();
            $this->get(route('public.imagens.show', $derivacao))->assertNotFound();
        }
    }

    public function test_pesquisa_e_filtros_combinados_permanecem_coerentes_entre_paginas(): void
    {
        $categoria = Categoria::create(['titulo' => 'Integração urbana']);
        $outraCategoria = Categoria::create(['titulo' => 'Integração rural']);
        $assunto = Assunto::create(['titulo' => 'Celebrações públicas']);
        $outroAssunto = Assunto::create(['titulo' => 'Transportes']);

        $correspondentes = collect(range(1, 13))->map(function (int $indice) use ($categoria, $assunto): ItemAcervo {
            $fotografia = $this->fotografia([
                'titulo' => sprintf('Desfile integrado %02d', $indice),
                'tipo_data' => 'ano',
                'ano' => 1985,
            ]);
            $this->arquivo($fotografia, 'thumbnail');
            $fotografia->categorias()->attach($categoria);
            $fotografia->assuntos()->attach($assunto);

            return $fotografia;
        });

        $this->fotografiaClassificada(
            ['titulo' => 'Construção sem o termo', 'tipo_data' => 'ano', 'ano' => 1985],
            $categoria,
            $assunto,
        );
        $this->fotografiaClassificada(
            ['titulo' => 'Desfile fora da década', 'tipo_data' => 'ano', 'ano' => 1975],
            $categoria,
            $assunto,
        );
        $this->fotografiaClassificada(
            ['titulo' => 'Desfile de outra categoria', 'tipo_data' => 'ano', 'ano' => 1985],
            $outraCategoria,
            $assunto,
        );
        $this->fotografiaClassificada(
            ['titulo' => 'Desfile de outro assunto', 'tipo_data' => 'ano', 'ano' => 1985],
            $categoria,
            $outroAssunto,
        );
        $this->fotografiaClassificada(
            [
                'titulo' => 'Desfile privado correspondente',
                'tipo_data' => 'ano',
                'ano' => 1985,
                'visibilidade' => Visibilidade::Privado,
            ],
            $categoria,
            $assunto,
        );
        $excluida = $this->fotografiaClassificada(
            ['titulo' => 'Desfile excluído correspondente', 'tipo_data' => 'ano', 'ano' => 1985],
            $categoria,
            $assunto,
        );
        $excluida->delete();

        $parametros = [
            'q' => 'Desfile integrado',
            'decada' => '1980',
            'categoria' => $categoria->id,
            'assunto' => $assunto->id,
        ];

        $primeiraResposta = $this->get(route('public.catalogo', $parametros))->assertOk();
        $primeiraPagina = $primeiraResposta->viewData('fotografias');
        $segundaResposta = $this->get(route('public.catalogo', [...$parametros, 'page' => 2]))->assertOk();
        $segundaPagina = $segundaResposta->viewData('fotografias');

        $this->assertCount(12, $primeiraPagina->items());
        $this->assertCount(1, $segundaPagina->items());
        $this->assertSame(13, $primeiraPagina->total());
        $this->assertSame(13, $segundaPagina->total());

        $idsPrimeiraPagina = collect($primeiraPagina->items())->pluck('id');
        $idsSegundaPagina = collect($segundaPagina->items())->pluck('id');

        $this->assertTrue($idsPrimeiraPagina->intersect($idsSegundaPagina)->isEmpty());
        $this->assertEqualsCanonicalizing(
            $correspondentes->pluck('id')->all(),
            $idsPrimeiraPagina->merge($idsSegundaPagina)->all(),
        );
        $this->assertParametrosNaUrl($primeiraPagina->nextPageUrl(), [...$parametros, 'page' => '2']);
        $this->assertParametrosNaUrl($segundaPagina->previousPageUrl(), [...$parametros, 'page' => '1']);

        foreach ([
            'Construção sem o termo',
            'Desfile fora da década',
            'Desfile de outra categoria',
            'Desfile de outro assunto',
            'Desfile privado correspondente',
            'Desfile excluído correspondente',
        ] as $tituloInelegivel) {
            $primeiraResposta->assertDontSee($tituloInelegivel);
            $segundaResposta->assertDontSee($tituloInelegivel);
        }
    }

    /** @param array<string, mixed> $attributes */
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

    /** @param array<string, mixed> $attributes */
    private function arquivo(ItemAcervo $fotografia, string $versao, array $attributes = []): Arquivo
    {
        $arquivo = Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'nome_original' => $versao === 'original' ? 'fotografia-original.jpg' : null,
            'provider' => 'local',
            'storage_path' => "acervo/{$fotografia->id}/{$versao}.jpg",
            'mime_type' => 'image/jpeg',
            'file_size' => strlen(self::CONTEUDO_DERIVACAO),
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => $versao,
            'width' => 800,
            'height' => 600,
            ...$attributes,
        ]);

        Storage::disk('local')->put(
            $arquivo->storage_path,
            $versao === 'original' ? 'conteudo-original' : self::CONTEUDO_DERIVACAO,
        );

        return $arquivo;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fotografiaClassificada(
        array $attributes,
        Categoria $categoria,
        Assunto $assunto,
    ): ItemAcervo {
        $fotografia = $this->fotografia($attributes);
        $this->arquivo($fotografia, 'thumbnail');
        $fotografia->categorias()->attach($categoria);
        $fotografia->assuntos()->attach($assunto);

        return $fotografia;
    }

    private function assertFotografiaPublicamenteDisponivel(ItemAcervo $fotografia, Arquivo $derivacao): void
    {
        $this->get(route('public.home'))->assertOk()->assertSee($fotografia->titulo);
        $this->get(route('public.catalogo', ['q' => $fotografia->titulo]))
            ->assertOk()
            ->assertSee($fotografia->titulo);
        $this->get(route('public.fotografias.show', $fotografia))->assertOk();
        $this->get(route('public.imagens.show', $derivacao))->assertOk();
    }

    /** @param array<string, int|string> $esperados */
    private function assertParametrosNaUrl(?string $url, array $esperados): void
    {
        $this->assertNotNull($url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $parametros);

        foreach ($esperados as $nome => $valor) {
            $this->assertSame((string) $valor, (string) ($parametros[$nome] ?? null));
        }
    }
}
