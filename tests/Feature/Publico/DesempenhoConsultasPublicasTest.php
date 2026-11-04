<?php

namespace Tests\Feature\Publico;

use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Colecao;
use App\Models\ConjuntoContextual;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DesempenhoConsultasPublicasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ItemAcervo::preventLazyLoading();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        ItemAcervo::preventLazyLoading(false);

        parent::tearDown();
    }

    public function test_telas_publicas_mantem_quantidade_constante_de_consultas_sem_lazy_loading(): void
    {
        $fotografias = collect(range(1, 5))->map(function (int $indice): ItemAcervo {
            $fotografia = $this->fotografia(['titulo' => "Consulta pública {$indice}"]);
            $this->arquivo($fotografia, 'thumbnail');
            $this->arquivo($fotografia, 'medium');
            $this->arquivo($fotografia, 'large');

            return $fotografia;
        });

        $detalhada = $fotografias->first();
        $autor = Autor::create(['nome' => 'Autoria pública', 'tipo' => 'instituicao']);
        $detalhada->update(['autor_id' => $autor->id]);
        $detalhada->categorias()->attach(Categoria::create(['titulo' => 'Categoria pública']));
        $detalhada->assuntos()->attach(Assunto::create(['titulo' => 'Assunto público']));
        $detalhada->palavrasChave()->attach(PalavraChave::create(['termo' => 'memória']));
        $detalhada->pessoas()->attach(Pessoa::create(['nome' => 'Pessoa identificada']));
        $detalhada->colecoes()->attach(Colecao::create(['titulo' => 'Coleção pública', 'status' => 'publicada']));
        $detalhada->conjuntosContextuais()->attach(
            ConjuntoContextual::create(['titulo' => 'Contexto público']),
            ['ordem' => 1],
        );

        [$home, $consultasHome] = $this->executarContandoConsultas(
            fn (): TestResponse => $this->get(route('public.home')),
        );
        [$catalogo, $consultasCatalogo] = $this->executarContandoConsultas(
            fn (): TestResponse => $this->get(route('public.catalogo')),
        );
        [$detalhe, $consultasDetalhe] = $this->executarContandoConsultas(
            fn (): TestResponse => $this->get(route('public.fotografias.show', $detalhada)),
        );

        $derivacao = $detalhada->arquivos()->where('versao_arquivo', 'medium')->firstOrFail();
        Storage::disk('local')->put($derivacao->storage_path, 'imagem-publica');
        [$imagem, $consultasImagem] = $this->executarContandoConsultas(
            fn (): TestResponse => $this->get(route('public.imagens.show', $derivacao)),
        );

        $fotografiaComUmaDerivacao = $this->fotografia(['titulo' => 'Consulta com uma derivação']);
        $derivacaoUnica = $this->arquivo($fotografiaComUmaDerivacao, 'thumbnail');
        Storage::disk('local')->put($derivacaoUnica->storage_path, 'imagem-publica');
        [$imagemUnica, $consultasImagemUnica] = $this->executarContandoConsultas(
            fn (): TestResponse => $this->get(route('public.imagens.show', $derivacaoUnica)),
        );

        $home->assertOk();
        $catalogo->assertOk();
        $detalhe->assertOk();
        $imagem->assertOk();
        $imagemUnica->assertOk();
        $this->assertSame(2, $consultasHome);
        $this->assertSame(6, $consultasCatalogo);
        $this->assertSame(9, $consultasDetalhe);
        $this->assertSame(3, $consultasImagem);
        $this->assertSame($consultasImagem, $consultasImagemUnica);
    }

    public function test_catalogo_mantem_paginacao_ordenacao_filtros_e_consultas_com_quinhentos_registros(): void
    {
        $categoria = Categoria::create(['titulo' => 'Volume urbano']);
        $assunto = Assunto::create(['titulo' => 'Volume histórico']);
        $ids = $this->inserirFotografiasEmVolume(
            quantidade: 500,
            prefixo: 'Volume representativo',
            categoria: $categoria,
            assunto: $assunto,
        );
        $parametros = [
            'q' => 'Volume representativo',
            'decada' => '1980',
            'categoria' => $categoria->id,
            'assunto' => $assunto->id,
        ];

        [$primeiraResposta, $consultasPrimeiraPagina] = $this->executarContandoConsultas(
            fn (): TestResponse => $this->get(route('public.catalogo', $parametros)),
        );
        [$segundaResposta, $consultasSegundaPagina] = $this->executarContandoConsultas(
            fn (): TestResponse => $this->get(route('public.catalogo', [...$parametros, 'page' => 2])),
        );
        [$ultimaResposta, $consultasUltimaPagina] = $this->executarContandoConsultas(
            fn (): TestResponse => $this->get(route('public.catalogo', [...$parametros, 'page' => 42])),
        );

        $primeiraPagina = $primeiraResposta->viewData('fotografias');
        $segundaPagina = $segundaResposta->viewData('fotografias');
        $ultimaPagina = $ultimaResposta->viewData('fotografias');
        $idsOrdenados = $ids->sortDesc()->values();

        $primeiraResposta->assertOk();
        $segundaResposta->assertOk();
        $ultimaResposta->assertOk();
        $this->assertSame(500, $primeiraPagina->total());
        $this->assertSame(42, $primeiraPagina->lastPage());
        $this->assertCount(12, $primeiraPagina->items());
        $this->assertCount(12, $segundaPagina->items());
        $this->assertCount(8, $ultimaPagina->items());
        $this->assertSame($idsOrdenados->take(12)->all(), collect($primeiraPagina->items())->pluck('id')->all());
        $this->assertSame($idsOrdenados->slice(12, 12)->values()->all(), collect($segundaPagina->items())->pluck('id')->all());
        $this->assertTrue(
            collect($primeiraPagina->items())->pluck('id')
                ->intersect(collect($segundaPagina->items())->pluck('id'))
                ->isEmpty(),
        );
        $this->assertParametrosNaUrl($primeiraPagina->nextPageUrl(), [...$parametros, 'page' => '2']);
        $this->assertSame(6, $consultasPrimeiraPagina);
        $this->assertSame($consultasPrimeiraPagina, $consultasSegundaPagina);
        $this->assertSame($consultasPrimeiraPagina, $consultasUltimaPagina);
    }

    public function test_opcoes_de_decada_sao_reduzidas_no_banco_antes_da_hidratacao(): void
    {
        $this->inserirFotografiasEmVolume(300, 'Mesmo ano conhecido');
        $this->inserirFotografiasEmVolume(
            quantidade: 100,
            prefixo: 'Data desconhecida',
            tipoData: 'desconhecida',
            ano: null,
        );

        DB::flushQueryLog();
        DB::enableQueryLog();
        $decadas = app(ConsultaFotografiasPublicas::class)->decadasDisponiveis();
        $consultas = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame(['1980'], $decadas);
        $this->assertCount(1, $consultas);
        $this->assertStringContainsString('select distinct', strtolower($consultas[0]['query']));
    }

    /** @param array<string, mixed> $attributes */
    private function fotografia(array $attributes = []): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => 'Fotografia pública '.uniqid(),
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1985,
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
            ...$attributes,
        ]);
    }

    private function arquivo(ItemAcervo $fotografia, string $versao): Arquivo
    {
        return Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'provider' => 'local',
            'storage_path' => "acervo/{$fotografia->id}/{$versao}.jpg",
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => $versao,
            'width' => $versao === 'thumbnail' ? 320 : 1024,
            'height' => $versao === 'thumbnail' ? 240 : 768,
        ]);
    }

    /**
     * @return Collection<int, int>
     */
    private function inserirFotografiasEmVolume(
        int $quantidade,
        string $prefixo,
        string $tipoData = 'ano',
        ?int $ano = 1985,
        ?Categoria $categoria = null,
        ?Assunto $assunto = null,
    ): Collection {
        $inicio = Carbon::parse('2020-01-01 00:00:00');
        $titulos = collect(range(1, $quantidade))
            ->map(fn (int $indice): string => sprintf('%s %04d', $prefixo, $indice));

        $titulos->map(fn (string $titulo, int $indice): array => [
            'tipo_item' => 'fotografia',
            'titulo' => $titulo,
            'tipo_data' => $tipoData,
            'ano' => $ano,
            'status' => 'publicado',
            'visibilidade' => 'publico',
            'created_at' => $inicio->copy()->addSeconds($indice),
            'updated_at' => $inicio->copy()->addSeconds($indice),
        ])->chunk(100)->each(fn (Collection $lote) => DB::table('item_acervos')->insert($lote->all()));

        $itens = DB::table('item_acervos')
            ->whereIn('titulo', $titulos)
            ->orderBy('id')
            ->get(['id', 'titulo']);

        $itens->map(fn (object $item): array => [
            'item_acervo_id' => $item->id,
            'provider' => 'local',
            'storage_path' => "acervo/{$item->id}/thumbnail.jpg",
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'thumbnail',
            'width' => 320,
            'height' => 240,
            'created_at' => $inicio,
            'updated_at' => $inicio,
        ])->chunk(100)->each(fn (Collection $lote) => DB::table('arquivos')->insert($lote->all()));

        if ($categoria !== null) {
            $itens->map(fn (object $item): array => [
                'categoria_id' => $categoria->id,
                'item_acervo_id' => $item->id,
            ])->chunk(100)->each(fn (Collection $lote) => DB::table('categoria_item_acervo')->insert($lote->all()));
        }

        if ($assunto !== null) {
            $itens->map(fn (object $item): array => [
                'assunto_id' => $assunto->id,
                'item_acervo_id' => $item->id,
            ])->chunk(100)->each(fn (Collection $lote) => DB::table('assunto_item_acervo')->insert($lote->all()));
        }

        return $itens->pluck('id')->map(fn (int|string $id): int => (int) $id);
    }

    /** @return array{TestResponse, int} */
    private function executarContandoConsultas(callable $acao): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $resposta = $acao();
            $quantidade = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }

        return [$resposta, $quantidade];
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
