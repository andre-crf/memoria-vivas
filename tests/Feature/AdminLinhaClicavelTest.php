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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cada linha das tabelas administrativas leva ao seu elemento por um link esticado sobre a linha.
 */
class AdminLinhaClicavelTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_table_links_its_row_and_keeps_actions_above_the_link(): void
    {
        $admin = User::factory()->create(['nome' => 'Administradora', 'role' => 'admin', 'status' => 'ativo']);
        $casos = [
            ['admin.fotografias.index', 'admin.fotografias.show', $this->fotografia('Coreto'), 'Coreto'],
            ['admin.categorias.index', 'admin.categorias.edit', Categoria::create(['titulo' => 'Praças']), 'Praças'],
            ['admin.assuntos.index', 'admin.assuntos.edit', Assunto::create(['titulo' => 'Urbanização']), 'Urbanização'],
            ['admin.palavras-chave.index', 'admin.palavras-chave.edit', PalavraChave::create(['termo' => 'coreto']), 'coreto'],
            ['admin.autores.index', 'admin.autores.edit', Autor::create(['nome' => 'Foto Estúdio', 'tipo' => 'pessoa']), 'Foto Estúdio'],
            ['admin.pessoas.index', 'admin.pessoas.edit', Pessoa::create(['nome' => 'Maria']), 'Maria'],
            ['admin.usuarios.index', 'admin.usuarios.edit', $admin, 'Administradora'],
        ];

        foreach ($casos as [$listagem, $destino, $registro, $rotulo]) {
            $html = $this->actingAs($admin)->get(route($listagem))->assertOk()->getContent();

            $this->assertLinhaLeva($html, route($destino, $registro), $rotulo, $listagem);
            $this->assertStringContainsString('relative z-10', $html, $listagem);
        }
    }

    public function test_view_links_were_removed_from_tables_with_a_detail_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
        $this->fotografia('Coreto');
        $contribuicao = Contribuicao::create(['nome' => 'Ana', 'email' => 'ana@memorias.test', 'titulo' => 'Fotos da praça', 'autorizacao' => true]);

        $this->actingAs($admin)->get(route('admin.fotografias.index'))->assertDontSee('Visualizar');

        $html = $this->actingAs($admin)->get(route('admin.contribuicoes.index'))
            ->assertDontSee('Ver detalhes')
            ->getContent();
        $this->assertLinhaLeva($html, route('admin.contribuicoes.show', $contribuicao), 'Fotos da praça', 'contribuições');
    }

    public function test_trash_rows_are_not_links_because_deleted_photos_have_no_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
        $this->fotografia('Excluída')->delete();

        $this->actingAs($admin)->get(route('admin.fotografias.trashed'))
            ->assertOk()
            ->assertSee('Excluída')
            ->assertDontSee('after:absolute', false);
    }

    private function assertLinhaLeva(string $html, string $url, string $rotulo, string $contexto): void
    {
        $padrao = '/<tr\b[^>]*class="[^"]*\brelative\b[^"]*"[^>]*>.*?<a\b(?=[^>]*href="'.preg_quote($url, '/').'")(?=[^>]*after:absolute)[^>]*>\s*'.preg_quote($rotulo, '/').'\s*<\/a>/s';

        $this->assertMatchesRegularExpression($padrao, $html, $contexto);
    }

    private function fotografia(string $titulo): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => $titulo,
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
    }
}
