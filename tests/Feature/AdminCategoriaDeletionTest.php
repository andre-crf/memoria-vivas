<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoriaDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    public function test_guest_cannot_delete_category(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição da categoria.',
        ]);

        $this
            ->delete(route('admin.categorias.destroy', $categoria))
            ->assertRedirect('/login');

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'titulo' => 'Arquitetura',
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição da categoria.',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->delete(route('admin.categorias.destroy', $categoria))
            ->assertRedirect(route('admin.categorias.index'))
            ->assertSessionHas('success', 'Categoria excluída com sucesso.');

        $this->assertDatabaseMissing('categorias', [
            'id' => $categoria->id,
        ]);
    }

    public function test_operator_can_delete_category(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Eventos',
            'descricao' => 'Descrição da categoria.',
        ]);

        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->delete(route('admin.categorias.destroy', $categoria))
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseMissing('categorias', [
            'id' => $categoria->id,
        ]);
    }

    public function test_deleting_category_removes_its_associations(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição da categoria.',
        ]);

        $item = ItemAcervo::create([
            'tipo_item' => 'fotografia',
            'titulo' => 'Fotografia da praça',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'desconhecido',
            'status' => 'rascunho',
            'visibilidade' => 'privado',
        ]);

        $categoria->itensAcervo()->attach($item);

        $this->assertDatabaseHas('categoria_item_acervo', [
            'categoria_id' => $categoria->id,
            'item_acervo_id' => $item->id,
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->delete(route('admin.categorias.destroy', $categoria))
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseMissing('categorias', [
            'id' => $categoria->id,
        ]);

        $this->assertDatabaseMissing('categoria_item_acervo', [
            'categoria_id' => $categoria->id,
            'item_acervo_id' => $item->id,
        ]);
    }

    public function test_deleting_category_does_not_delete_associated_items(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição da categoria.',
        ]);

        $item = ItemAcervo::create([
            'tipo_item' => 'fotografia',
            'titulo' => 'Fotografia da praça',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'desconhecido',
            'status' => 'rascunho',
            'visibilidade' => 'privado',
        ]);

        $categoria->itensAcervo()->attach($item);

        $this
            ->actingAs($this->usuarioInterno())
            ->delete(route('admin.categorias.destroy', $categoria))
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseHas('item_acervos', [
            'id' => $item->id,
            'titulo' => 'Fotografia da praça',
        ]);
    }

    public function test_category_listing_displays_number_of_associated_items(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Descrição da categoria.',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $item = ItemAcervo::create([
                'tipo_item' => 'fotografia',
                'titulo' => "Fotografia {$i}",
                'tipo_data' => 'ano',
                'ano' => 1980,
                'estado_conservacao' => 'desconhecido',
                'status' => 'rascunho',
                'visibilidade' => 'privado',
            ]);

            $categoria->itensAcervo()->attach($item);
        }

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.categorias.index'))
            ->assertOk()
            ->assertSee('Arquitetura')
            ->assertSee('3');
    }
}