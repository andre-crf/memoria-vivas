<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoriaUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    public function test_guest_is_redirected_from_edit_category_form(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição original.',
        ]);

        $this
            ->get(route('admin.categorias.edit', $categoria))
            ->assertRedirect('/login');
    }

    public function test_admin_and_operator_can_access_edit_category_form(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição original.',
        ]);

        foreach (['admin', 'operador'] as $role) {
            $this
                ->actingAs($this->usuarioInterno($role))
                ->get(route('admin.categorias.edit', $categoria))
                ->assertOk()
                ->assertSee('Editar categoria')
                ->assertSee('Arquitetura')
                ->assertSee('Descrição original.')
                ->assertSee('Salvar alterações');
        }
    }

    public function test_admin_can_update_category(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição original.',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.categorias.update', $categoria), [
                'titulo' => 'Arquitetura histórica',
                'descricao' => 'Nova descrição da categoria.',
            ])
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'titulo' => 'Arquitetura histórica',
            'descricao' => 'Nova descrição da categoria.',
        ]);
    }

    public function test_operator_can_update_category(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Eventos',
            'descricao' => 'Descrição original.',
        ]);

        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->put(route('admin.categorias.update', $categoria), [
                'titulo' => 'Eventos históricos',
                'descricao' => 'Registros de eventos históricos.',
            ])
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'titulo' => 'Eventos históricos',
            'descricao' => 'Registros de eventos históricos.',
        ]);
    }

    public function test_update_validates_required_title(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição original.',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.categorias.edit', $categoria))
            ->put(route('admin.categorias.update', $categoria), [
                'titulo' => '',
                'descricao' => 'Nova descrição.',
            ])
            ->assertRedirect(route('admin.categorias.edit', $categoria))
            ->assertSessionHasErrors('titulo');

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição original.',
        ]);
    }

    public function test_category_can_keep_its_own_title_when_updated(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição original.',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.categorias.update', $categoria), [
                'titulo' => 'Arquitetura',
                'descricao' => 'Descrição atualizada.',
            ])
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição atualizada.',
        ]);
    }

    public function test_update_rejects_title_already_used_by_another_category(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Primeira categoria.',
        ]);

        $outraCategoria = Categoria::create([
            'titulo' => 'Eventos',
            'descricao' => 'Segunda categoria.',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.categorias.edit', $categoria))
            ->put(route('admin.categorias.update', $categoria), [
                'titulo' => 'Eventos',
                'descricao' => 'Tentativa de usar título existente.',
            ])
            ->assertRedirect(route('admin.categorias.edit', $categoria))
            ->assertSessionHasErrors('titulo');

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'titulo' => 'Arquitetura',
            'descricao' => 'Primeira categoria.',
        ]);

        $this->assertDatabaseHas('categorias', [
            'id' => $outraCategoria->id,
            'titulo' => 'Eventos',
            'descricao' => 'Segunda categoria.',
        ]);
    }

    public function test_update_allows_empty_description(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição original.',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.categorias.update', $categoria), [
                'titulo' => 'Arquitetura',
                'descricao' => '',
            ])
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'titulo' => 'Arquitetura',
            'descricao' => null,
        ]);
    }
}