<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\Categoria;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFotografiaForceDeleteTest extends TestCase
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
            'titulo' => 'Fotografia na lixeira',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
        ]);
    }

    public function test_trash_listing_shows_permanent_delete_action_with_irreversible_warning(): void
    {
        $fotografia = $this->fotografia();
        $fotografia->delete();

        $this
            ->actingAs($this->usuarioInterno('admin'))
            ->get(route('admin.fotografias.trashed'))
            ->assertOk()
            ->assertSee('Atenção:')
            ->assertSee('Essa operação é irreversível.')
            ->assertSee('Excluir permanentemente')
            ->assertSee(route('admin.fotografias.force-destroy', $fotografia->id), false)
            ->assertSee("return confirm('Excluir permanentemente esta fotografia? Esta operação é irreversível.')", false);
    }

    public function test_admin_can_permanently_delete_soft_deleted_photograph(): void
    {
        $fotografia = $this->fotografia();
        $fotografia->delete();

        $this
            ->actingAs($this->usuarioInterno('admin'))
            ->delete(route('admin.fotografias.force-destroy', $fotografia->id))
            ->assertRedirect(route('admin.fotografias.trashed'))
            ->assertSessionHas('success', 'Fotografia excluída permanentemente.');

        $this->assertDatabaseMissing('item_acervos', [
            'id' => $fotografia->id,
        ]);
    }

    public function test_permanent_delete_cascades_related_records(): void
    {
        $fotografia = $this->fotografia();
        $categoria = Categoria::create(['titulo' => 'Fotografia urbana']);
        $fotografia->categorias()->attach($categoria);

        Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'nome_original' => 'fotografia.jpg',
            'provider' => 'local',
            'storage_path' => 'acervo/fotografia.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 2048,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'original',
        ]);

        $fotografia->delete();

        $this
            ->actingAs($this->usuarioInterno('admin'))
            ->delete(route('admin.fotografias.force-destroy', $fotografia->id))
            ->assertRedirect(route('admin.fotografias.trashed'));

        $this->assertDatabaseMissing('item_acervos', [
            'id' => $fotografia->id,
        ]);
        $this->assertDatabaseMissing('arquivos', [
            'item_acervo_id' => $fotografia->id,
        ]);
        $this->assertDatabaseMissing('categoria_item_acervo', [
            'item_acervo_id' => $fotografia->id,
        ]);
        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
        ]);
    }

    public function test_operator_cannot_permanently_delete_photograph(): void
    {
        $fotografia = $this->fotografia();
        $fotografia->delete();

        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->delete(route('admin.fotografias.force-destroy', $fotografia->id))
            ->assertForbidden();

        $this->assertSoftDeleted('item_acervos', [
            'id' => $fotografia->id,
        ]);
    }

    public function test_permanent_delete_only_accepts_soft_deleted_photographs(): void
    {
        $admin = $this->usuarioInterno('admin');
        $fotografiaAtiva = $this->fotografia(['titulo' => 'Fotografia ativa']);
        $documentoExcluido = $this->fotografia([
            'titulo' => 'Documento excluído',
            'tipo_item' => 'documento',
        ]);
        $documentoExcluido->delete();

        $this
            ->actingAs($admin)
            ->delete(route('admin.fotografias.force-destroy', $fotografiaAtiva->id))
            ->assertNotFound();

        $this
            ->actingAs($admin)
            ->delete(route('admin.fotografias.force-destroy', $documentoExcluido->id))
            ->assertNotFound();

        $this->assertDatabaseHas('item_acervos', [
            'id' => $fotografiaAtiva->id,
        ]);
        $this->assertSoftDeleted('item_acervos', [
            'id' => $documentoExcluido->id,
        ]);
    }
}
