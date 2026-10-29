<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Contribuicao;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Ordenação por colunas das listagens administrativas.
 */
class AdminOrdenacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_historical_dates_follow_one_rule_and_undated_items_stay_last(): void
    {
        // Criados fora de ordem para que o ID não coincida com a ordem esperada.
        $this->fotografia('Sem data A', ['tipo_data' => 'desconhecida']);
        $this->fotografia('10/03/1965', ['tipo_data' => 'data_exata', 'dia' => 10, 'mes' => 3, 'ano' => 1965]);
        $this->fotografia('Década de 1970', ['tipo_data' => 'decada', 'decada' => '1970']);
        $this->fotografia('Década de 1960', ['tipo_data' => 'decada', 'decada' => '1960']);
        $this->fotografia('Março de 1965', ['tipo_data' => 'mes_ano', 'mes' => 3, 'ano' => 1965]);
        $this->fotografia('Sem data B', ['tipo_data' => 'desconhecida']);
        $this->fotografia('Ano de 1965', ['tipo_data' => 'ano', 'ano' => 1965]);
        $this->fotografia('Ano de 1960', ['tipo_data' => 'ano', 'ano' => 1960]);

        // Início do período; empate resolvido pela precisão (ano antes de década); sem data no fim.
        $this->assertSame(
            ['Ano de 1960', 'Década de 1960', 'Ano de 1965', 'Março de 1965', '10/03/1965', 'Década de 1970', 'Sem data A', 'Sem data B'],
            $this->titulos($this->listagem('admin.fotografias.index', ['ordem' => 'data', 'direcao' => 'asc'])),
        );
        $this->assertSame(
            ['Década de 1970', '10/03/1965', 'Março de 1965', 'Ano de 1965', 'Ano de 1960', 'Década de 1960', 'Sem data B', 'Sem data A'],
            $this->titulos($this->listagem('admin.fotografias.index', ['ordem' => 'data', 'direcao' => 'desc'])),
        );
    }

    public function test_trash_orders_by_historical_date_and_by_deletion_without_showing_active_items(): void
    {
        $antiga = $this->fotografia('Excluída de 1950', ['tipo_data' => 'ano', 'ano' => 1950]);
        $recente = $this->fotografia('Excluída sem data', ['tipo_data' => 'desconhecida']);
        $this->fotografia('Ativa de 1940', ['tipo_data' => 'ano', 'ano' => 1940]);
        Carbon::setTestNow('2026-10-01 10:00:00');
        $recente->delete();
        Carbon::setTestNow('2026-10-02 10:00:00');
        $antiga->delete();
        Carbon::setTestNow();

        $this->assertSame(['Excluída de 1950', 'Excluída sem data'], $this->titulos($this->listagem('admin.fotografias.trashed')));
        $this->assertSame(['Excluída sem data', 'Excluída de 1950'], $this->titulos($this->listagem('admin.fotografias.trashed', ['ordem' => 'exclusao', 'direcao' => 'asc'])));
        $this->assertSame(['Excluída de 1950', 'Excluída sem data'], $this->titulos($this->listagem('admin.fotografias.trashed', ['ordem' => 'data', 'direcao' => 'desc'])));
    }

    public function test_status_and_visibility_follow_the_workflow_order(): void
    {
        $this->fotografia('Publicada', ['status' => 'publicado', 'visibilidade' => Visibilidade::Publico]);
        $this->fotografia('Arquivada', ['status' => 'arquivado']);
        $this->fotografia('Rascunho', ['status' => 'rascunho']);
        $this->fotografia('Em revisão', ['status' => 'em_revisao', 'visibilidade' => Visibilidade::Publico]);

        $this->assertSame(['Rascunho', 'Em revisão', 'Publicada', 'Arquivada'], $this->titulos($this->listagem('admin.fotografias.index', ['ordem' => 'status', 'direcao' => 'asc'])));
        $this->assertSame(['Arquivada', 'Publicada', 'Em revisão', 'Rascunho'], $this->titulos($this->listagem('admin.fotografias.index', ['ordem' => 'status', 'direcao' => 'desc'])));
        // Público antes de privado; dentro do mesmo valor, desempate pelo ID na mesma direção.
        $this->assertSame(['Publicada', 'Em revisão', 'Arquivada', 'Rascunho'], $this->titulos($this->listagem('admin.fotografias.index', ['ordem' => 'visibilidade', 'direcao' => 'asc'])));
    }

    public function test_photo_listing_without_parameters_keeps_the_newest_first(): void
    {
        $this->fotografia('Primeira');
        $this->fotografia('Segunda');
        $this->fotografia('Terceira');

        $resposta = $this->listagem('admin.fotografias.index')
            ->assertSee('aria-sort="none"', false)
            ->assertDontSee('aria-sort="ascending"', false);

        $this->assertSame(['Terceira', 'Segunda', 'Primeira'], $this->titulos($resposta));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function cadastros(): array
    {
        return [
            'categorias' => ['admin.categorias.index', 'categorias', 'titulo'],
            'assuntos' => ['admin.assuntos.index', 'assuntos', 'titulo'],
            'palavras-chave' => ['admin.palavras-chave.index', 'palavrasChave', 'termo'],
            'autores' => ['admin.autores.index', 'autores', 'nome'],
            'pessoas' => ['admin.pessoas.index', 'pessoas', 'nome'],
        ];
    }

    #[DataProvider('cadastros')]
    public function test_catalogs_order_by_label_and_by_associated_items(string $rota, string $chave, string $rotulo): void
    {
        $this->cadastro($chave, 'Bom Jesus', 1);
        $this->cadastro($chave, 'Avenida', 3);
        $this->cadastro($chave, 'Coreto', 0);

        $ordem = fn (array $parametros): array => collect($this->listagem($rota, $parametros)->viewData($chave)->items())->pluck($rotulo)->all();

        $this->assertSame(['Avenida', 'Bom Jesus', 'Coreto'], $ordem([]));
        $this->assertSame(['Coreto', 'Bom Jesus', 'Avenida'], $ordem(['ordem' => $rotulo, 'direcao' => 'desc']));
        $this->assertSame(['Avenida', 'Bom Jesus', 'Coreto'], $ordem(['ordem' => 'itens', 'direcao' => 'desc']));
        $this->assertSame(['Coreto', 'Bom Jesus', 'Avenida'], $ordem(['ordem' => 'itens', 'direcao' => 'asc']));
    }

    public function test_authors_and_users_order_enum_columns_consistently(): void
    {
        Autor::create(['nome' => 'Ana', 'tipo' => 'instituicao']);
        Autor::create(['nome' => 'Bruno', 'tipo' => 'pessoa']);
        $tipos = collect($this->listagem('admin.autores.index', ['ordem' => 'tipo', 'direcao' => 'asc'])->viewData('autores')->items())->pluck('nome')->all();
        $this->assertSame(['Bruno', 'Ana'], $tipos);

        User::factory()->create(['nome' => 'Zélia', 'email' => 'a@memorias.test', 'role' => 'operador', 'status' => 'inativo']);
        User::factory()->create(['nome' => 'Yara', 'email' => 'b@memorias.test', 'role' => 'operador', 'status' => 'ativo']);
        $usuarios = fn (array $parametros): array => collect($this->listagem('admin.usuarios.index', $parametros)->viewData('usuarios')->items())->pluck('nome')->all();

        // Administrador antes de operador; inativo depois de ativo. Empates seguem o ID na mesma direção.
        $this->assertSame(['Administradora', 'Zélia', 'Yara'], $usuarios(['ordem' => 'perfil', 'direcao' => 'asc']));
        $this->assertSame(['Zélia', 'Yara', 'Administradora'], $usuarios(['ordem' => 'situacao', 'direcao' => 'desc']));
        // a@… < admin@… < b@…
        $this->assertSame(['Zélia', 'Administradora', 'Yara'], $usuarios(['ordem' => 'email', 'direcao' => 'asc']));
    }

    public function test_contributions_order_by_subject_contributor_files_and_date(): void
    {
        $antiga = $this->contribuicao('Coreto', 'Bruno', 2, '2026-10-01 09:00:00');
        $this->contribuicao('Avenida', 'Carla', 0, '2026-10-03 09:00:00');
        $this->contribuicao('Bom Jesus', 'Ana', 1, '2026-10-02 09:00:00');
        $ordem = fn (array $parametros): array => collect($this->listagem('admin.contribuicoes.index', $parametros)->viewData('contribuicoes')->items())->pluck('titulo')->all();

        $this->assertSame(['Avenida', 'Bom Jesus', 'Coreto'], $ordem([]));
        $this->assertSame(['Coreto', 'Bom Jesus', 'Avenida'], $ordem(['ordem' => 'recebida', 'direcao' => 'asc']));
        $this->assertSame(['Avenida', 'Bom Jesus', 'Coreto'], $ordem(['ordem' => 'assunto', 'direcao' => 'asc']));
        $this->assertSame(['Bom Jesus', 'Coreto', 'Avenida'], $ordem(['ordem' => 'colaborador', 'direcao' => 'asc']));
        $this->assertSame(['Coreto', 'Bom Jesus', 'Avenida'], $ordem(['ordem' => 'arquivos', 'direcao' => 'desc']));
        $this->assertSame(2, $antiga->arquivos()->count());
    }

    public function test_unknown_columns_and_directions_fall_back_to_the_default_without_reaching_sql(): void
    {
        $this->admin();
        $this->fotografia('Antiga');
        $this->fotografia('Recente');
        $padrao = ['Recente', 'Antiga'];

        foreach ([
            ['ordem' => 'password'],
            ['ordem' => 'titulo; drop table users'],
            ['ordem' => 'id'],
            ['ordem' => ['titulo']],
            ['ordem' => 'recentes', 'direcao' => 'lado'],
            ['direcao' => 'desc; --'],
        ] as $parametros) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $titulos = $this->titulos($this->listagem('admin.fotografias.index', $parametros)->assertOk());
            $sql = collect(DB::getQueryLog())->pluck('query')->join(' ');
            DB::disableQueryLog();

            $this->assertSame($padrao, $titulos, json_encode($parametros));
            $this->assertStringNotContainsString('drop table', $sql);
            $this->assertStringNotContainsString('password', $sql);
            $this->assertStringNotContainsString('lado', $sql);
            $this->assertStringNotContainsString('--', $sql);
        }

        // Uma chave válida numa direção inválida usa a direção natural da coluna.
        $this->assertSame(['Antiga', 'Recente'], $this->titulos($this->listagem('admin.fotografias.index', ['ordem' => 'titulo', 'direcao' => 'cima'])));
    }

    public function test_sorting_keeps_search_filters_and_pagination_together(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            $this->fotografia(sprintf('Coreto %02d', $i), ['status' => 'publicado', 'tipo_data' => 'ano', 'ano' => 1900 + $i]);
        }

        $this->fotografia('Coreto rascunho', ['status' => 'rascunho', 'tipo_data' => 'ano', 'ano' => 1800]);
        $parametros = ['q' => 'Coreto', 'status' => 'publicado', 'ordem' => 'data', 'direcao' => 'asc'];

        $resposta = $this->listagem('admin.fotografias.index', $parametros);
        $paginador = $resposta->viewData('fotografias');

        $this->assertSame('Coreto 01', $this->titulos($resposta)[0]);
        parse_str((string) parse_url((string) $paginador->nextPageUrl(), PHP_URL_QUERY), $proxima);
        $this->assertEqualsCanonicalizing([...$parametros, 'page' => '2'], $proxima);
        $this->assertSame(['Coreto 16', 'Coreto 17'], $this->titulos($this->listagem('admin.fotografias.index', [...$parametros, 'page' => 2])));

        // O formulário de pesquisa leva a ordenação, e os cabeçalhos levam pesquisa e filtros, sem a página.
        $resposta
            ->assertSee('<input type="hidden" name="ordem" value="data">', false)
            ->assertSee('<input type="hidden" name="direcao" value="asc">', false)
            ->assertSee('aria-sort="ascending"', false);
        $this->assertSame(
            ['q' => 'Coreto', 'status' => 'publicado', 'ordem' => 'data', 'direcao' => 'desc'],
            $this->linkDoCabecalho($resposta, 'data'),
        );
        $this->assertSame(
            ['q' => 'Coreto', 'status' => 'publicado', 'ordem' => 'titulo', 'direcao' => 'asc'],
            $this->linkDoCabecalho($resposta, 'titulo'),
        );
    }

    public function test_catalog_headers_keep_the_search_and_show_the_direction(): void
    {
        Categoria::create(['titulo' => 'Praças']);
        Categoria::create(['titulo' => 'Pracinhas']);

        $resposta = $this->listagem('admin.categorias.index', ['q' => 'pra', 'ordem' => 'titulo', 'direcao' => 'desc'])
            ->assertSee('aria-sort="descending"', false)
            ->assertSee('Ordenar por título, ordem crescente', false);

        // Inverter para título crescente volta ao padrão: a URL fica só com a pesquisa.
        $this->assertSame(['q' => 'pra'], $this->linkDoCabecalho($resposta, 'titulo'));
        $this->assertSame(['q' => 'pra', 'ordem' => 'itens', 'direcao' => 'desc'], $this->linkDoCabecalho($resposta, 'itens'));
    }

    public function test_query_count_does_not_change_with_sorting(): void
    {
        $this->admin();
        ItemAcervo::preventLazyLoading();

        for ($i = 1; $i <= 20; $i++) {
            $this->fotografia("Coreto {$i}", ['tipo_data' => 'ano', 'ano' => 1900 + $i]);
        }

        $contar = function (array $parametros): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            try {
                $this->listagem('admin.fotografias.index', $parametros)->assertOk();

                return count(DB::getQueryLog());
            } finally {
                DB::disableQueryLog();
            }
        };

        $base = $contar([]);

        foreach (['titulo', 'data', 'status', 'visibilidade'] as $coluna) {
            $this->assertSame($base, $contar(['ordem' => $coluna, 'direcao' => 'desc', 'page' => 2]), $coluna);
        }

        ItemAcervo::preventLazyLoading(false);
    }

    /** @param array<string, mixed> $parametros */
    private function listagem(string $rota, array $parametros = []): TestResponse
    {
        return $this->actingAs($this->admin())->get(route($rota, $parametros))->assertOk();
    }

    /** @return list<string> */
    private function titulos(TestResponse $resposta): array
    {
        return collect($resposta->viewData('fotografias')->items())->pluck('titulo')->all();
    }

    /**
     * Parâmetros do link do cabeçalho de uma coluna.
     *
     * @return array<string, string>
     */
    private function linkDoCabecalho(TestResponse $resposta, string $chave): array
    {
        preg_match('/<a\s+href="([^"]*)"[^>]*data-ordem="'.preg_quote($chave, '/').'"/s', (string) $resposta->getContent(), $link);
        $this->assertNotEmpty($link, "Cabeçalho {$chave} não encontrado.");
        parse_str((string) parse_url(html_entity_decode($link[1]), PHP_URL_QUERY), $parametros);
        $this->assertArrayNotHasKey('page', $parametros);

        return $parametros;
    }

    /** @param array<string, mixed> $dados */
    private function fotografia(string $titulo, array $dados = []): ItemAcervo
    {
        return ItemAcervo::create($dados + [
            'titulo' => $titulo,
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
    }

    private function cadastro(string $chave, string $rotulo, int $itens): Model
    {
        $registro = match ($chave) {
            'categorias' => Categoria::create(['titulo' => $rotulo]),
            'assuntos' => Assunto::create(['titulo' => $rotulo]),
            'palavrasChave' => PalavraChave::create(['termo' => $rotulo]),
            'autores' => Autor::create(['nome' => $rotulo, 'tipo' => 'pessoa']),
            'pessoas' => Pessoa::create(['nome' => $rotulo]),
        };

        for ($i = 0; $i < $itens; $i++) {
            $item = $this->fotografia("Item {$rotulo} {$i}", $chave === 'autores' ? ['autor_id' => $registro->getKey()] : []);

            if ($chave !== 'autores') {
                $registro->itensAcervo()->attach($item);
            }
        }

        return $registro;
    }

    private function contribuicao(string $titulo, string $nome, int $arquivos, string $recebidaEm): Contribuicao
    {
        Carbon::setTestNow($recebidaEm);
        $contribuicao = Contribuicao::create(['nome' => $nome, 'email' => 'c@memorias.test', 'titulo' => $titulo, 'autorizacao' => true]);

        for ($i = 0; $i < $arquivos; $i++) {
            $contribuicao->arquivos()->create(['nome_original' => "foto{$i}.jpg", 'caminho' => "contribuicoes/{$contribuicao->id}/{$i}.jpg", 'mime_type' => 'image/jpeg', 'tamanho' => 100]);
        }

        Carbon::setTestNow();

        return $contribuicao;
    }

    private function admin(): User
    {
        return User::query()->firstWhere('email', 'admin@memorias.test')
            ?? User::factory()->create(['nome' => 'Administradora', 'email' => 'admin@memorias.test', 'role' => 'admin', 'status' => 'ativo']);
    }
}
