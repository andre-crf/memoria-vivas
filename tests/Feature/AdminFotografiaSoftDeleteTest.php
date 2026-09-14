<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFotografiaSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function fotografia(array $dados = []): ItemAcervo
    {
        return ItemAcervo::create($dados + [
            'titulo' => 'Fotografia para exclusão',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
        ]);
    }

    public function test_listing_delete_action_asks_for_confirmation(): void
    {
        $fotografia = $this->fotografia();

        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->get(route('admin.fotografias.index'))
            ->assertOk()
            ->assertSee(route('admin.fotografias.destroy', $fotografia), false)
            ->assertSee("return confirm('Excluir esta fotografia?')", false)
            ->assertSee('Excluir');
    }

    public function test_internal_user_can_soft_delete_photograph(): void
    {
        foreach (['admin', 'operador'] as $role) {
            $user = $this->usuarioInterno($role);
            $fotografia = $this->fotografia([
                'titulo' => "Fotografia removida por {$role}",
            ]);

            $this
                ->actingAs($user)
                ->delete(route('admin.fotografias.destroy', $fotografia))
                ->assertRedirect(route('admin.fotografias.index'))
                ->assertSessionHas('success', 'Fotografia excluída com sucesso.');

            $this->assertSoftDeleted('item_acervos', [
                'id' => $fotografia->id,
            ]);

            $this->assertDatabaseHas('item_acervos', [
                'id' => $fotografia->id,
                'deleted_by_user_id' => $user->id,
            ]);
        }
    }

    public function test_soft_deleted_photograph_leaves_normal_admin_flows(): void
    {
        $user = $this->usuarioInterno();
        $fotografia = $this->fotografia();

        $this
            ->actingAs($user)
            ->delete(route('admin.fotografias.destroy', $fotografia));

        $this
            ->actingAs($user)
            ->get(route('admin.fotografias.index'))
            ->assertOk()
            ->assertDontSee('Fotografia para exclusão');

        $this
            ->actingAs($user)
            ->get(route('admin.fotografias.show', $fotografia->id))
            ->assertNotFound();

        $this
            ->actingAs($user)
            ->get(route('admin.fotografias.edit', $fotografia->id))
            ->assertNotFound();
    }

    public function test_soft_deleted_photograph_is_not_publicly_displayable(): void
    {
        $fotografia = $this->fotografia();

        $this->assertTrue($fotografia->podeSerExibidoPublicamente());

        $this
            ->actingAs($this->usuarioInterno())
            ->delete(route('admin.fotografias.destroy', $fotografia));

        $fotografiaExcluida = ItemAcervo::withTrashed()->findOrFail($fotografia->id);

        $this->assertFalse($fotografiaExcluida->podeSerExibidoPublicamente());
    }

    public function test_delete_route_does_not_accept_non_photograph_items(): void
    {
        $documento = $this->fotografia([
            'titulo' => 'Documento administrativo',
            'tipo_item' => 'documento',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->delete(route('admin.fotografias.destroy', $documento))
            ->assertNotFound();

        $this->assertDatabaseHas('item_acervos', [
            'id' => $documento->id,
            'deleted_at' => null,
        ]);
    }
}
