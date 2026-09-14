<?php

namespace Tests\Feature;

use App\Models\Autor;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAutorDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_delete_author(): void
    {
        $autor = Autor::create([
            'nome' => 'Autor Teste',
            'tipo' => 'pessoa',
        ]);

        $response = $this->delete(
            route('admin.autores.destroy', $autor)
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('autores', [
            'id' => $autor->id,
        ]);
    }

    public function test_admin_can_delete_author(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'ativo',
        ]);

        $autor = Autor::create([
            'nome' => 'Autor Teste',
            'tipo' => 'pessoa',
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('admin.autores.destroy', $autor));

        $response
            ->assertRedirect(route('admin.autores.index'))
            ->assertSessionHas(
                'success',
                'Autor excluído com sucesso.'
            );

        $this->assertDatabaseMissing('autores', [
            'id' => $autor->id,
        ]);
    }

    public function test_operator_can_delete_author(): void
    {
        $operator = User::factory()->create([
            'role' => 'operador',
            'status' => 'ativo',
        ]);

        $autor = Autor::create([
            'nome' => 'Autor Teste',
            'tipo' => 'pessoa',
        ]);

        $response = $this
            ->actingAs($operator)
            ->delete(route('admin.autores.destroy', $autor));

        $response
            ->assertRedirect(route('admin.autores.index'))
            ->assertSessionHas(
                'success',
                'Autor excluído com sucesso.'
            );

        $this->assertDatabaseMissing('autores', [
            'id' => $autor->id,
        ]);
    }

    public function test_deleting_author_preserves_associated_items_and_removes_author_reference(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'ativo',
        ]);

        $autor = Autor::create([
            'nome' => 'Autor Teste',
            'tipo' => 'pessoa',
        ]);

        $item1 = ItemAcervo::create([
            'tipo_item' => 'fotografia',
            'titulo' => 'Fotografia 1',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'desconhecido',
            'status' => 'rascunho',
            'visibilidade' => 'privado',
            'autor_id' => $autor->id,
        ]);

        $item2 = ItemAcervo::create([
            'tipo_item' => 'fotografia',
            'titulo' => 'Fotografia 2',
            'tipo_data' => 'ano',
            'ano' => 1981,
            'estado_conservacao' => 'desconhecido',
            'status' => 'rascunho',
            'visibilidade' => 'privado',
            'autor_id' => $autor->id,
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.autores.destroy', $autor));

        $this->assertDatabaseMissing('autores', [
            'id' => $autor->id,
        ]);

        $this->assertDatabaseHas('item_acervos', [
            'id' => $item1->id,
            'autor_id' => null,
        ]);

        $this->assertDatabaseHas('item_acervos', [
            'id' => $item2->id,
            'autor_id' => null,
        ]);
    }

    public function test_deleting_author_without_associated_items_succeeds(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'ativo',
        ]);

        $autor = Autor::create([
            'nome' => 'Autor Sem Itens',
            'tipo' => 'pessoa',
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('admin.autores.destroy', $autor));

        $response
            ->assertRedirect(route('admin.autores.index'))
            ->assertSessionHas(
                'success',
                'Autor excluído com sucesso.'
            );

        $this->assertDatabaseMissing('autores', [
            'id' => $autor->id,
        ]);
    }
}