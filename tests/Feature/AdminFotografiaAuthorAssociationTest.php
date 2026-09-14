<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\Autor;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFotografiaAuthorAssociationTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    private function autor(string $nome = 'Foto Studio Umuarama', string $tipo = 'instituicao'): Autor
    {
        return Autor::create([
            'nome' => $nome,
            'tipo' => $tipo,
        ]);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function fotografia(array $dados = []): ItemAcervo
    {
        return ItemAcervo::create($dados + [
            'titulo' => 'Praça central em obras',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $override = []): array
    {
        return $override + [
            'titulo' => 'Praça central restaurada',
            'tipo_data' => 'ano',
            'ano' => 1981,
            'estado_conservacao' => 'regular',
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico->value,
        ];
    }

    public function test_create_and_edit_forms_show_author_selection(): void
    {
        $autor = $this->autor();
        $fotografia = $this->fotografia(['autor_id' => $autor->id]);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.create'))
            ->assertOk()
            ->assertSee('Autor')
            ->assertSee('Sem autor associado')
            ->assertSee('Foto Studio Umuarama');

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.edit', $fotografia))
            ->assertOk()
            ->assertSee('Autor')
            ->assertSee('Foto Studio Umuarama');
    }

    public function test_internal_user_can_register_photograph_with_author(): void
    {
        $autor = $this->autor();

        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'titulo' => 'Fotografia com autor',
                'autor_id' => $autor->id,
            ]))
            ->assertRedirect(route('admin.fotografias.index'));

        $this->assertDatabaseHas('item_acervos', [
            'titulo' => 'Fotografia com autor',
            'autor_id' => $autor->id,
        ]);
    }

    public function test_internal_user_can_register_photograph_without_author(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'titulo' => 'Fotografia sem autor',
                'autor_id' => '',
            ]))
            ->assertRedirect(route('admin.fotografias.index'));

        $fotografia = ItemAcervo::where('titulo', 'Fotografia sem autor')->firstOrFail();

        $this->assertNull($fotografia->autor_id);
    }

    public function test_internal_user_can_change_author_and_details_show_it(): void
    {
        $autorOriginal = $this->autor('José Juliani', 'pessoa');
        $novoAutor = $this->autor('Acervo Prefeitura Municipal');
        $fotografia = $this->fotografia(['autor_id' => $autorOriginal->id]);

        $this
            ->actingAs($this->usuarioInterno())
            ->followingRedirects()
            ->put(route('admin.fotografias.update', $fotografia), $this->validPayload([
                'autor_id' => $novoAutor->id,
            ]))
            ->assertOk()
            ->assertSee('Acervo Prefeitura Municipal')
            ->assertDontSee('José Juliani');

        $this->assertDatabaseHas('item_acervos', [
            'id' => $fotografia->id,
            'autor_id' => $novoAutor->id,
        ]);
    }

    public function test_internal_user_can_remove_author_association(): void
    {
        $autor = $this->autor();
        $fotografia = $this->fotografia(['autor_id' => $autor->id]);

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.fotografias.update', $fotografia), $this->validPayload([
                'autor_id' => '',
            ]))
            ->assertRedirect(route('admin.fotografias.show', $fotografia));

        $fotografia->refresh();

        $this->assertNull($fotografia->autor_id);
    }

    public function test_author_must_exist(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.create'))
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'autor_id' => 999,
            ]))
            ->assertRedirect(route('admin.fotografias.create'))
            ->assertSessionHasErrors('autor_id');
    }
}
