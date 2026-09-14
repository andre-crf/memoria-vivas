<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFotografiaTrashTest extends TestCase
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
            'titulo' => 'Fotografia excluída',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'data_exata',
            'dia' => 15,
            'mes' => 3,
            'ano' => 1980,
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
        ]);
    }

    public function test_admin_can_access_trash_listing_with_deleted_photographs(): void
    {
        $admin = $this->usuarioInterno('admin');
        $deletedBy = $this->usuarioInterno('operador');

        $fotografia = $this->fotografia([
            'titulo' => 'Praça removida',
        ]);

        $this->actingAs($deletedBy);
        $fotografia->delete();

        $this
            ->actingAs($admin)
            ->get(route('admin.fotografias.trashed'))
            ->assertOk()
            ->assertSee('Lixeira de fotografias')
            ->assertSee('Praça removida')
            ->assertSee('15/03/1980')
            ->assertSee('Publicado')
            ->assertSee('Público')
            ->assertSee($deletedBy->nome)
            ->assertSee('Restaurar')
            ->assertSee("return confirm('Restaurar esta fotografia?')", false);
    }

    public function test_operator_cannot_access_trash_or_restore_photographs(): void
    {
        $operator = $this->usuarioInterno('operador');
        $fotografia = $this->fotografia();
        $fotografia->delete();

        $this
            ->actingAs($operator)
            ->get(route('admin.fotografias.trashed'))
            ->assertForbidden();

        $this
            ->actingAs($operator)
            ->patch(route('admin.fotografias.restore', $fotografia->id))
            ->assertForbidden();

        $this->assertSoftDeleted('item_acervos', [
            'id' => $fotografia->id,
        ]);
    }

    public function test_admin_can_restore_soft_deleted_photograph(): void
    {
        $admin = $this->usuarioInterno('admin');
        $deletedBy = $this->usuarioInterno('operador');
        $fotografia = $this->fotografia([
            'titulo' => 'Fotografia restaurável',
        ]);

        $this->actingAs($deletedBy);
        $fotografia->delete();

        $this
            ->actingAs($admin)
            ->patch(route('admin.fotografias.restore', $fotografia->id))
            ->assertRedirect(route('admin.fotografias.trashed'))
            ->assertSessionHas('success', 'Fotografia restaurada com sucesso.');

        $this->assertDatabaseHas('item_acervos', [
            'id' => $fotografia->id,
            'deleted_at' => null,
            'deleted_by_user_id' => null,
            'updated_by_user_id' => $admin->id,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.fotografias.index'))
            ->assertOk()
            ->assertSee('Fotografia restaurável');
    }

    public function test_trash_listing_only_shows_deleted_photographs(): void
    {
        $fotografiaExcluida = $this->fotografia(['titulo' => 'Foto na lixeira']);
        $fotografiaExcluida->delete();

        $this->fotografia(['titulo' => 'Foto ativa']);

        $documentoExcluido = $this->fotografia([
            'titulo' => 'Documento excluído',
            'tipo_item' => 'documento',
        ]);
        $documentoExcluido->delete();

        $this
            ->actingAs($this->usuarioInterno('admin'))
            ->get(route('admin.fotografias.trashed'))
            ->assertOk()
            ->assertSee('Foto na lixeira')
            ->assertDontSee('Foto ativa')
            ->assertDontSee('Documento excluído');
    }

    public function test_trash_listing_has_empty_state(): void
    {
        $this
            ->actingAs($this->usuarioInterno('admin'))
            ->get(route('admin.fotografias.trashed'))
            ->assertOk()
            ->assertSee('Nenhuma fotografia na lixeira');
    }

    public function test_restore_route_does_not_accept_active_or_non_photograph_items(): void
    {
        $admin = $this->usuarioInterno('admin');
        $fotografiaAtiva = $this->fotografia(['titulo' => 'Foto ativa']);
        $documentoExcluido = $this->fotografia([
            'titulo' => 'Documento excluído',
            'tipo_item' => 'documento',
        ]);
        $documentoExcluido->delete();

        $this
            ->actingAs($admin)
            ->patch(route('admin.fotografias.restore', $fotografiaAtiva->id))
            ->assertNotFound();

        $this
            ->actingAs($admin)
            ->patch(route('admin.fotografias.restore', $documentoExcluido->id))
            ->assertNotFound();
    }
}
