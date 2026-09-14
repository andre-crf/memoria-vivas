<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFotografiaEditingTest extends TestCase
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
            'titulo' => 'Praça central em obras',
            'tipo_item' => 'fotografia',
            'legenda' => 'Registro da reforma da praça central.',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'local_atual' => 'Praça Santos Dumont',
            'local_epoca' => 'Praça Central',
            'evento' => 'Reforma urbana',
            'cedente' => 'Família Silva',
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
            'legenda' => 'Registro revisado da fotografia.',
            'tipo_data' => 'mes_ano',
            'mes' => 5,
            'ano' => 1981,
            'local_atual' => 'Praça Santos Dumont',
            'local_epoca' => 'Praça Central antiga',
            'evento' => 'Comemoração municipal',
            'cedente' => 'Arquivo Municipal',
            'estado_conservacao' => 'regular',
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico->value,
        ];
    }

    public function test_guest_is_redirected_from_edit_photograph_form(): void
    {
        $fotografia = $this->fotografia();

        $this
            ->get(route('admin.fotografias.edit', $fotografia))
            ->assertRedirect('/login');
    }

    public function test_admin_and_operator_can_access_edit_photograph_form_with_current_data(): void
    {
        foreach (['admin', 'operador'] as $role) {
            $fotografia = $this->fotografia([
                'titulo' => "Fotografia editável {$role}",
                'tipo_data' => 'data_exata',
                'dia' => 10,
                'mes' => 4,
                'ano' => 1978,
            ]);

            $this
                ->actingAs($this->usuarioInterno($role))
                ->get(route('admin.fotografias.edit', $fotografia))
                ->assertOk()
                ->assertSee('Editar fotografia')
                ->assertSee("Fotografia editável {$role}")
                ->assertSee('Registro da reforma da praça central.')
                ->assertSee('Praça Santos Dumont')
                ->assertSee('Salvar alterações');
        }
    }

    public function test_internal_user_can_update_photograph_basic_information(): void
    {
        $updater = $this->usuarioInterno('operador');
        $fotografia = $this->fotografia();

        $this
            ->actingAs($updater)
            ->followingRedirects()
            ->put(route('admin.fotografias.update', $fotografia), $this->validPayload())
            ->assertOk()
            ->assertSee('Fotografia atualizada com sucesso.')
            ->assertSee('Praça central restaurada')
            ->assertSee('05/1981')
            ->assertSee('Publicado')
            ->assertSee('Público');

        $this->assertDatabaseHas('item_acervos', [
            'id' => $fotografia->id,
            'tipo_item' => 'fotografia',
            'titulo' => 'Praça central restaurada',
            'legenda' => 'Registro revisado da fotografia.',
            'tipo_data' => 'mes_ano',
            'dia' => null,
            'mes' => 5,
            'ano' => 1981,
            'decada' => null,
            'local_epoca' => 'Praça Central antiga',
            'evento' => 'Comemoração municipal',
            'cedente' => 'Arquivo Municipal',
            'estado_conservacao' => 'regular',
            'status' => 'publicado',
            'visibilidade' => 'publico',
            'updated_by_user_id' => $updater->id,
        ]);
    }

    public function test_update_validates_required_fields_and_keeps_current_data(): void
    {
        $fotografia = $this->fotografia();

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.edit', $fotografia))
            ->put(route('admin.fotografias.update', $fotografia), [])
            ->assertRedirect(route('admin.fotografias.edit', $fotografia))
            ->assertSessionHasErrors([
                'titulo',
                'tipo_data',
                'estado_conservacao',
                'status',
                'visibilidade',
            ]);

        $this->assertDatabaseHas('item_acervos', [
            'id' => $fotografia->id,
            'titulo' => 'Praça central em obras',
        ]);
    }

    public function test_update_validates_date_precision_fields(): void
    {
        $fotografia = $this->fotografia();

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.edit', $fotografia))
            ->put(route('admin.fotografias.update', $fotografia), $this->validPayload([
                'tipo_data' => 'decada',
                'ano' => 1981,
                'decada' => '1980',
            ]))
            ->assertRedirect(route('admin.fotografias.edit', $fotografia))
            ->assertSessionHasErrors('tipo_data');
    }

    public function test_edit_and_update_do_not_accept_non_photograph_items(): void
    {
        $documento = $this->fotografia([
            'titulo' => 'Documento administrativo',
            'tipo_item' => 'documento',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.edit', $documento))
            ->assertNotFound();

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.fotografias.update', $documento), $this->validPayload())
            ->assertNotFound();
    }
}
