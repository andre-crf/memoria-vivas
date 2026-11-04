<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\Autor;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminFotografiaSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ItemAcervo::preventLazyLoading(false);

        parent::tearDown();
    }

    public function test_search_covers_title_and_the_relevant_catalog_fields(): void
    {
        $autor = Autor::create(['nome' => 'Foto Estúdio Umuarama', 'tipo' => 'instituicao']);
        $campos = [
            'Pelo título Santos Dumont' => [],
            'Pela legenda' => ['legenda' => 'Vista da Santos Dumont em obras'],
            'Pelo local atual' => ['local_atual' => 'Avenida Santos Dumont'],
            'Pelo local da época' => ['local_epoca' => 'Rua Santos Dumont'],
            'Pelo evento' => ['evento' => 'Inauguração da Santos Dumont'],
            'Pelo cedente' => ['cedente' => 'Família Santos Dumont'],
            'Pela localização física' => ['localizacao_fisica' => 'Arquivo Santos Dumont, caixa 3'],
        ];

        foreach ($campos as $titulo => $dados) {
            $this->fotografia(['titulo' => $titulo, ...$dados]);
        }

        $this->fotografia(['titulo' => 'Pelo autor', 'autor_id' => $autor->id]);
        $this->fotografia(['titulo' => 'Sem relação']);

        $this->listagem(['q' => 'santos dumont'])
            ->assertOk()
            ->assertSee(array_keys($campos))
            ->assertDontSee('Pelo autor')
            ->assertDontSee('Sem relação')
            ->assertSee('7 fotografias encontradas');

        $this->listagem(['q' => 'Estúdio Umuarama'])
            ->assertSee('Pelo autor')
            ->assertDontSee('Pelo título')
            ->assertSee('1 fotografia encontrada');
    }

    public function test_search_finds_by_id_and_treats_wildcards_as_text(): void
    {
        $alvo = $this->fotografia(['titulo' => 'Coreto restaurado']);
        $this->fotografia(['titulo' => '100% preservada']);
        $this->fotografia(['titulo' => 'Foto_arquivo']);
        $this->fotografia(['titulo' => 'Outra fotografia']);

        $this->listagem(['q' => "#{$alvo->id}"])->assertSee('Coreto restaurado')->assertDontSee('Outra fotografia');
        $this->listagem(['q' => (string) $alvo->id])->assertSee('Coreto restaurado');
        $this->listagem(['q' => '%'])->assertSee('100% preservada')->assertDontSee('Outra fotografia');
        $this->listagem(['q' => '_'])->assertSee('Foto_arquivo')->assertDontSee('Outra fotografia');
    }

    public function test_status_and_visibility_filters_combine_with_the_search(): void
    {
        $this->fotografia(['titulo' => 'Coreto publicado público', 'status' => 'publicado', 'visibilidade' => Visibilidade::Publico]);
        $this->fotografia(['titulo' => 'Coreto publicado privado', 'status' => 'publicado', 'visibilidade' => Visibilidade::Privado]);
        $this->fotografia(['titulo' => 'Coreto rascunho público', 'status' => 'rascunho', 'visibilidade' => Visibilidade::Publico]);
        $this->fotografia(['titulo' => 'Praça publicada pública', 'status' => 'publicado', 'visibilidade' => Visibilidade::Publico]);

        $this->listagem(['status' => 'publicado'])
            ->assertSee(['Coreto publicado público', 'Coreto publicado privado', 'Praça publicada pública'])
            ->assertDontSee('Coreto rascunho público');

        $this->listagem(['visibilidade' => 'publico'])
            ->assertSee(['Coreto publicado público', 'Coreto rascunho público', 'Praça publicada pública'])
            ->assertDontSee('Coreto publicado privado');

        $this->listagem(['q' => 'Coreto', 'status' => 'publicado', 'visibilidade' => 'publico'])
            ->assertSee('Coreto publicado público')
            ->assertDontSee(['Coreto publicado privado', 'Coreto rascunho público', 'Praça publicada pública'])
            ->assertSee('<option value="publicado" selected>', false)
            ->assertSee('<option value="publico" selected>', false)
            ->assertSee('value="Coreto"', false);
    }

    public function test_invalid_filter_values_are_ignored_and_a_long_search_is_rejected(): void
    {
        $this->fotografia(['titulo' => 'Fotografia qualquer']);

        $this->listagem(['status' => 'inexistente', 'visibilidade' => 'secreta'])
            ->assertOk()
            ->assertSee('Fotografia qualquer')
            ->assertDontSee('Limpar filtros');

        $this->listagem(['q' => str_repeat('a', 201)])
            ->assertStatus(422)
            ->assertSee('A pesquisa deve ter no máximo 200 caracteres.')
            ->assertSee('Fotografia qualquer');

        $this->listagem(['q' => ['array']])
            ->assertStatus(422)
            ->assertSee('Digite um texto válido para pesquisar.');
    }

    public function test_pagination_keeps_fifteen_per_page_and_preserves_search_and_filters(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            $this->fotografia(['titulo' => sprintf('Coreto %02d', $i), 'status' => 'publicado', 'visibilidade' => Visibilidade::Publico]);
        }

        $this->fotografia(['titulo' => 'Coreto rascunho', 'status' => 'rascunho']);

        $primeira = $this->listagem(['q' => 'Coreto', 'status' => 'publicado', 'visibilidade' => 'publico']);
        $paginador = $primeira->viewData('fotografias');

        $this->assertSame(15, $paginador->perPage());
        $this->assertSame(17, $paginador->total());
        $this->assertCount(15, $paginador->items());
        $this->assertLinkPreservaFiltros($paginador->nextPageUrl(), ['q' => 'Coreto', 'status' => 'publicado', 'visibilidade' => 'publico', 'page' => 2]);

        $this->listagem(['q' => 'Coreto', 'status' => 'publicado', 'visibilidade' => 'publico', 'page' => 2])
            ->assertSee(['Coreto 01', 'Coreto 02'])
            ->assertDontSee('Coreto 03')
            ->assertDontSee('Coreto rascunho');
    }

    public function test_search_without_results_shows_its_own_empty_state(): void
    {
        $this->fotografia(['titulo' => 'Praça central']);

        $this->listagem(['q' => 'inexistente'])
            ->assertOk()
            ->assertSee('Nenhuma fotografia encontrada para os filtros aplicados')
            ->assertSee('Limpar filtros')
            ->assertSee('0 fotografias encontradas')
            ->assertDontSee('Nenhuma fotografia cadastrada');

        ItemAcervo::query()->delete();

        $this->listagem()
            ->assertSee('Nenhuma fotografia cadastrada')
            ->assertDontSee('Limpar filtros');
    }

    public function test_listing_never_returns_trashed_items_or_other_item_types(): void
    {
        $this->fotografia(['titulo' => 'Coreto ativo']);
        $this->fotografia(['titulo' => 'Coreto na lixeira'])->delete();
        $this->fotografia(['titulo' => 'Coreto documento', 'tipo_item' => 'documento']);

        $this->listagem(['q' => 'Coreto'])
            ->assertSee('Coreto ativo')
            ->assertDontSee(['Coreto na lixeira', 'Coreto documento']);
    }

    public function test_trash_searches_and_filters_only_soft_deleted_photographs(): void
    {
        $this->fotografia(['titulo' => 'Coreto ativo', 'status' => 'publicado']);
        $this->fotografia(['titulo' => 'Coreto excluído publicado', 'status' => 'publicado'])->delete();
        $this->fotografia(['titulo' => 'Coreto excluído rascunho', 'status' => 'rascunho'])->delete();
        $this->fotografia(['titulo' => 'Praça excluída', 'status' => 'publicado'])->delete();
        $this->fotografia(['titulo' => 'Coreto documento excluído', 'tipo_item' => 'documento'])->delete();

        $this->lixeira(['q' => 'Coreto'])
            ->assertOk()
            ->assertSee(['Coreto excluído publicado', 'Coreto excluído rascunho'])
            ->assertDontSee(['Coreto ativo', 'Praça excluída', 'Coreto documento excluído']);

        $this->lixeira(['q' => 'Coreto', 'status' => 'publicado'])
            ->assertSee('Coreto excluído publicado')
            ->assertDontSee('Coreto excluído rascunho');

        $this->lixeira(['q' => 'Coreto ativo'])
            ->assertSee('Nenhuma fotografia encontrada para os filtros aplicados')
            ->assertSee(route('admin.fotografias.trashed'), false);
    }

    public function test_trash_pagination_preserves_filters_and_operators_stay_without_access(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            $this->fotografia(['titulo' => sprintf('Excluída %02d', $i), 'visibilidade' => Visibilidade::Publico])->delete();
        }

        $paginador = $this->lixeira(['visibilidade' => 'publico'])->viewData('fotografias');

        $this->assertSame(16, $paginador->total());
        $this->assertCount(15, $paginador->items());
        $this->assertLinkPreservaFiltros($paginador->nextPageUrl(), ['visibilidade' => 'publico', 'page' => 2]);

        $this->actingAs($this->usuario('operador'))
            ->get(route('admin.fotografias.trashed', ['q' => 'Excluída']))
            ->assertForbidden();
    }

    public function test_query_count_does_not_grow_with_pages_or_filters(): void
    {
        ItemAcervo::preventLazyLoading();
        $autor = Autor::create(['nome' => 'Foto Estúdio', 'tipo' => 'instituicao']);

        for ($i = 1; $i <= 32; $i++) {
            $item = $this->fotografia(['titulo' => "Coreto {$i}", 'status' => 'publicado', 'autor_id' => $autor->id]);
            $item->arquivos()->create([
                'provider' => 'local', 'storage_path' => "acervo/derivados/{$item->id}/thumb.webp", 'mime_type' => 'image/webp',
                'file_size' => 100, 'tipo_arquivo' => 'imagem', 'versao_arquivo' => 'thumbnail', 'width' => 320, 'height' => 240,
            ]);

            if ($i > 16) {
                $item->delete();
            }
        }

        $admin = $this->usuario();
        $contar = fn (string $rota, array $parametros): int => $this->contarConsultas(
            fn () => $this->actingAs($admin)->get(route($rota, $parametros))->assertOk(),
        );

        foreach (['admin.fotografias.index', 'admin.fotografias.trashed'] as $rota) {
            $semFiltros = $contar($rota, []);
            $this->assertSame($semFiltros, $contar($rota, ['page' => 2]));

            $comFiltros = $contar($rota, ['q' => 'Coreto', 'status' => 'publicado', 'visibilidade' => 'privado']);
            $this->assertSame($comFiltros, $contar($rota, ['q' => 'Coreto', 'status' => 'publicado', 'visibilidade' => 'privado', 'page' => 2]));
            $this->assertSame($semFiltros, $comFiltros);
        }
    }

    /** @param array<string, mixed> $parametros */
    private function listagem(array $parametros = []): TestResponse
    {
        return $this->actingAs($this->usuario())->get(route('admin.fotografias.index', $parametros));
    }

    /** @param array<string, mixed> $parametros */
    private function lixeira(array $parametros = []): TestResponse
    {
        return $this->actingAs($this->usuario())->get(route('admin.fotografias.trashed', $parametros));
    }

    private function contarConsultas(callable $acao): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $acao();

            return count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
    }

    /** @param array<string, int|string> $esperados */
    private function assertLinkPreservaFiltros(?string $url, array $esperados): void
    {
        $this->assertNotNull($url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $parametros);
        ksort($parametros);
        ksort($esperados);

        $this->assertSame(array_map('strval', $esperados), $parametros);
    }

    private function usuario(string $role = 'admin'): User
    {
        return User::firstOrCreate(
            ['email' => "{$role}@memorias.test"],
            User::factory()->make(['role' => $role, 'status' => 'ativo'])->only(['nome', 'password', 'role', 'status']),
        );
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function fotografia(array $dados = []): ItemAcervo
    {
        return ItemAcervo::create($dados + [
            'titulo' => fake()->sentence(3),
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
    }
}
