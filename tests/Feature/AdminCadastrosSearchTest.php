<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Busca e paginação das listagens de categorias, assuntos, palavras-chave, autores, pessoas e usuários.
 */
class AdminCadastrosSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function cadastros(): array
    {
        return [
            'categorias' => ['categorias'],
            'assuntos' => ['assuntos'],
            'palavras-chave' => ['palavras-chave'],
            'autores' => ['autores'],
            'pessoas' => ['pessoas'],
            'usuários' => ['usuarios'],
        ];
    }

    /** Rota, chave na view, coluna exibida (e de ordenação), colunas pesquisadas, mensagens e se o operador acessa. */
    private const DEFINICOES = [
        'categorias' => ['rota' => 'admin.categorias.index', 'chave' => 'categorias', 'rotulo' => 'titulo', 'colunas' => ['titulo', 'descricao'], 'vazio' => 'Nenhuma categoria cadastrada', 'sem_resultado' => 'Nenhuma categoria encontrada para a pesquisa', 'operador' => true],
        'assuntos' => ['rota' => 'admin.assuntos.index', 'chave' => 'assuntos', 'rotulo' => 'titulo', 'colunas' => ['titulo', 'descricao'], 'vazio' => 'Nenhum assunto cadastrado', 'sem_resultado' => 'Nenhum assunto encontrado para a pesquisa', 'operador' => true],
        'palavras-chave' => ['rota' => 'admin.palavras-chave.index', 'chave' => 'palavrasChave', 'rotulo' => 'termo', 'colunas' => ['termo'], 'vazio' => 'Nenhuma palavra-chave cadastrada', 'sem_resultado' => 'Nenhuma palavra-chave encontrada para a pesquisa', 'operador' => true],
        'autores' => ['rota' => 'admin.autores.index', 'chave' => 'autores', 'rotulo' => 'nome', 'colunas' => ['nome', 'observacao'], 'vazio' => 'Nenhum autor cadastrado', 'sem_resultado' => 'Nenhum autor encontrado para a pesquisa', 'operador' => true],
        'pessoas' => ['rota' => 'admin.pessoas.index', 'chave' => 'pessoas', 'rotulo' => 'nome', 'colunas' => ['nome', 'observacao'], 'vazio' => 'Nenhuma pessoa cadastrada', 'sem_resultado' => 'Nenhuma pessoa encontrada para a pesquisa', 'operador' => true],
        'usuarios' => ['rota' => 'admin.usuarios.index', 'chave' => 'usuarios', 'rotulo' => 'nome', 'colunas' => ['nome', 'email'], 'vazio' => null, 'sem_resultado' => 'Nenhum usuário encontrado para a pesquisa', 'operador' => false],
    ];

    #[DataProvider('cadastros')]
    public function test_search_matches_each_searchable_column_and_treats_wildcards_as_text(string $cadastro): void
    {
        $definicao = self::DEFINICOES[$cadastro];
        $admin = $this->admin();

        $esperados = [];

        foreach ($definicao['colunas'] as $indice => $coluna) {
            // Um registro por coluna pesquisada, com o termo só naquela coluna.
            if ($coluna === $definicao['rotulo']) {
                $esperados[] = $this->criar($cadastro, "Coreto {$indice}")->{$definicao['rotulo']};
            } else {
                $esperados[] = $this->criar($cadastro, "Encontrado {$indice}", [$coluna => $coluna === 'email' ? "coreto{$indice}@memorias.test" : "Sobre o coreto {$indice}"])->{$definicao['rotulo']};
            }
        }

        $this->criar($cadastro, 'Fora da busca');
        $this->criar($cadastro, 'Registro 100% cheio');

        $resposta = $this->actingAs($admin)->get(route($definicao['rota'], ['q' => 'coreto']))->assertOk();
        $nomes = collect($resposta->viewData($definicao['chave'])->items())->pluck($definicao['rotulo'])->all();

        $this->assertEqualsCanonicalizing($esperados, $nomes);
        $this->assertSame(['Registro 100% cheio'], collect($this->actingAs($admin)->get(route($definicao['rota'], ['q' => '%']))->viewData($definicao['chave'])->items())->pluck($definicao['rotulo'])->all());
    }

    #[DataProvider('cadastros')]
    public function test_pagination_has_fifteen_per_page_and_keeps_the_search(string $cadastro): void
    {
        $definicao = self::DEFINICOES[$cadastro];
        $admin = $this->admin();

        for ($i = 1; $i <= 17; $i++) {
            $this->criar($cadastro, sprintf('Coreto %02d', $i));
        }

        $this->criar($cadastro, 'Zzz fora da busca');

        $paginador = $this->actingAs($admin)->get(route($definicao['rota'], ['q' => 'Coreto']))->assertOk()->viewData($definicao['chave']);

        $this->assertSame(15, $paginador->perPage());
        $this->assertSame(17, $paginador->total());
        $this->assertCount(15, $paginador->items());
        parse_str((string) parse_url((string) $paginador->nextPageUrl(), PHP_URL_QUERY), $parametros);
        $this->assertSame(['q' => 'Coreto', 'page' => '2'], $parametros);

        $this->actingAs($admin)->get(route($definicao['rota'], ['q' => 'Coreto', 'page' => 2]))
            ->assertOk()
            ->assertSee(['Coreto 16', 'Coreto 17'])
            ->assertDontSee('Coreto 15');
    }

    #[DataProvider('cadastros')]
    public function test_empty_states_distinguish_search_without_results_from_empty_register(string $cadastro): void
    {
        $definicao = self::DEFINICOES[$cadastro];
        $admin = $this->admin();

        $this->actingAs($admin)->get(route($definicao['rota'], ['q' => 'inexistente']))
            ->assertOk()
            ->assertSee($definicao['sem_resultado'])
            ->assertSee('Limpar pesquisa');

        if ($definicao['vazio'] !== null) {
            $this->actingAs($admin)->get(route($definicao['rota']))
                ->assertOk()
                ->assertSee($definicao['vazio'])
                ->assertDontSee($definicao['sem_resultado'])
                ->assertDontSee('Limpar pesquisa');
        }
    }

    #[DataProvider('cadastros')]
    public function test_long_search_is_rejected_and_access_rules_are_kept(string $cadastro): void
    {
        $definicao = self::DEFINICOES[$cadastro];

        $this->actingAs($this->admin())->get(route($definicao['rota'], ['q' => str_repeat('a', 201)]))
            ->assertStatus(422)
            ->assertSee('A pesquisa deve ter no máximo 200 caracteres.');

        auth()->logout();
        $this->get(route($definicao['rota'], ['q' => 'coreto']))->assertRedirect(route('login'));

        $operador = $this->actingAs(User::factory()->create(['role' => 'operador', 'status' => 'ativo']))
            ->get(route($definicao['rota'], ['q' => 'coreto']));
        $definicao['operador'] ? $operador->assertOk() : $operador->assertForbidden();
    }

    #[DataProvider('cadastros')]
    public function test_query_count_does_not_grow_with_pages_or_search(string $cadastro): void
    {
        $definicao = self::DEFINICOES[$cadastro];
        $admin = $this->admin();

        for ($i = 1; $i <= 32; $i++) {
            $registro = $this->criar($cadastro, sprintf('Coreto %02d', $i));
            $this->associarItem($cadastro, $registro);
        }

        $contar = fn (array $parametros): int => $this->contarConsultas(
            fn () => $this->actingAs($admin)->get(route($definicao['rota'], $parametros))->assertOk(),
        );

        $base = $contar([]);
        $this->assertSame($base, $contar(['page' => 2]));
        $this->assertSame($base, $contar(['q' => 'Coreto']));
        $this->assertSame($base, $contar(['q' => 'Coreto', 'page' => 2]));
    }

    public function test_relationship_counts_are_kept_with_search_and_pagination(): void
    {
        $admin = $this->admin();

        foreach (['categorias', 'assuntos', 'palavras-chave', 'autores', 'pessoas'] as $cadastro) {
            $definicao = self::DEFINICOES[$cadastro];
            $registro = $this->criar($cadastro, 'Coreto com itens');
            $this->associarItem($cadastro, $registro);
            $this->associarItem($cadastro, $registro);
            $this->criar($cadastro, 'Coreto sem itens');

            $contagens = collect($this->actingAs($admin)->get(route($definicao['rota'], ['q' => 'Coreto']))->viewData($definicao['chave'])->items())
                ->mapWithKeys(fn (Model $item): array => [$item->{$definicao['rotulo']} => $item->itens_acervo_count]);

            $this->assertSame(['Coreto com itens' => 2, 'Coreto sem itens' => 0], $contagens->all(), $cadastro);
        }
    }

    /**
     * Cria um registro com o rótulo (título, termo ou nome) informado e campos extras.
     *
     * @param  array<string, string>  $extras
     */
    private function criar(string $cadastro, string $rotulo, array $extras = []): Model
    {
        $dados = $extras + match ($cadastro) {
            'categorias', 'assuntos' => ['titulo' => $rotulo],
            'palavras-chave' => ['termo' => $rotulo],
            'autores' => ['nome' => $rotulo, 'tipo' => 'pessoa'],
            'pessoas' => ['nome' => $rotulo],
            'usuarios' => ['nome' => $rotulo, 'email' => uniqid('usuario').'@memorias.test'],
        };

        return match ($cadastro) {
            'categorias' => Categoria::create($dados),
            'assuntos' => Assunto::create($dados),
            'palavras-chave' => PalavraChave::create($dados),
            'autores' => Autor::create($dados),
            'pessoas' => Pessoa::create($dados),
            'usuarios' => User::factory()->create($dados + ['role' => 'operador', 'status' => 'ativo']),
        };
    }

    private function associarItem(string $cadastro, Model $registro): void
    {
        if ($cadastro === 'usuarios') {
            return;
        }

        $item = ItemAcervo::create([
            'titulo' => 'Fotografia associada',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
            'autor_id' => $cadastro === 'autores' ? $registro->getKey() : null,
        ]);

        if ($cadastro !== 'autores') {
            $registro->itensAcervo()->attach($item);
        }
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

    private function admin(): User
    {
        return User::query()->firstWhere('email', 'admin@memorias.test')
            ?? User::factory()->create(['nome' => 'Administradora', 'email' => 'admin@memorias.test', 'role' => 'admin', 'status' => 'ativo']);
    }
}
