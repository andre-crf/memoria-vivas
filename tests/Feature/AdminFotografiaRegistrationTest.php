<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFotografiaRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    public function test_guest_is_redirected_from_create_photograph_form(): void
    {
        $this
            ->get(route('admin.fotografias.create'))
            ->assertRedirect('/login');
    }

    public function test_admin_and_operator_can_access_create_photograph_form(): void
    {
        foreach (['admin', 'operador'] as $role) {
            $this
                ->actingAs($this->usuarioInterno($role))
                ->get(route('admin.fotografias.create'))
                ->assertOk()
                ->assertSee('Cadastrar fotografia')
                ->assertSee('Título')
                ->assertSee('Legenda/descrição')
                ->assertSee('Precisão da data')
                ->assertSee('Local atual')
                ->assertSee('Local na época')
                ->assertSee('Evento relacionado')
                ->assertSee('Cedente')
                ->assertSee('Estado de conservação')
                ->assertSee('Visibilidade')
                ->assertSee('Status');
        }
    }

    public function test_admin_can_register_basic_photograph_information(): void
    {
        $admin = $this->usuarioInterno();

        $this
            ->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.fotografias.store'), [
                'titulo' => 'Praça central em obras',
                'legenda' => 'Registro da reforma da praça central.',
                'tipo_data' => 'data_exata',
                'dia' => 15,
                'mes' => 3,
                'ano' => 1980,
                'local_atual' => 'Praça Santos Dumont',
                'local_epoca' => 'Praça Central',
                'evento' => 'Reforma urbana',
                'cedente' => 'Família Silva',
                'estado_conservacao' => 'bom',
                'status' => 'rascunho',
                'visibilidade' => Visibilidade::Privado->value,
            ])
            ->assertOk()
            ->assertSee('Fotografia cadastrada com sucesso.')
            ->assertSee('Praça central em obras');

        $this->assertDatabaseHas('item_acervos', [
            'tipo_item' => 'fotografia',
            'titulo' => 'Praça central em obras',
            'legenda' => 'Registro da reforma da praça central.',
            'tipo_data' => 'data_exata',
            'dia' => 15,
            'mes' => 3,
            'ano' => 1980,
            'local_atual' => 'Praça Santos Dumont',
            'local_epoca' => 'Praça Central',
            'evento' => 'Reforma urbana',
            'cedente' => 'Família Silva',
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => 'privado',
            'created_by_user_id' => $admin->id,
        ]);
    }

    public function test_operator_can_register_photograph(): void
    {
        $operador = $this->usuarioInterno('operador');

        $this
            ->actingAs($operador)
            ->post(route('admin.fotografias.store'), [
                'titulo' => 'Foto cadastrada por operador',
                'tipo_data' => 'ano',
                'ano' => 1990,
                'estado_conservacao' => 'desconhecido',
                'status' => 'em_revisao',
                'visibilidade' => Visibilidade::Publico->value,
            ])
            ->assertRedirect(route('admin.fotografias.index'));

        $this->assertDatabaseHas('item_acervos', [
            'titulo' => 'Foto cadastrada por operador',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1990,
            'status' => 'em_revisao',
            'visibilidade' => 'publico',
            'created_by_user_id' => $operador->id,
        ]);
    }

    public function test_registration_validates_required_fields(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.create'))
            ->post(route('admin.fotografias.store'), [])
            ->assertRedirect(route('admin.fotografias.create'))
            ->assertSessionHasErrors([
                'titulo',
                'tipo_data',
                'estado_conservacao',
                'status',
                'visibilidade',
            ]);
    }

    public function test_registration_validates_date_precision_fields(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.create'))
            ->post(route('admin.fotografias.store'), [
                'titulo' => 'Foto com data inconsistente',
                'tipo_data' => 'ano',
                'dia' => 10,
                'ano' => 1980,
                'estado_conservacao' => 'bom',
                'status' => 'rascunho',
                'visibilidade' => Visibilidade::Privado->value,
            ])
            ->assertRedirect(route('admin.fotografias.create'))
            ->assertSessionHasErrors('tipo_data');

        $this->assertDatabaseMissing('item_acervos', [
            'titulo' => 'Foto com data inconsistente',
        ]);
    }

    public function test_optional_empty_fields_are_stored_as_null(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.fotografias.store'), [
                'titulo' => 'Foto sem metadados opcionais',
                'legenda' => '',
                'tipo_data' => 'desconhecida',
                'local_atual' => '',
                'local_epoca' => '',
                'evento' => '',
                'cedente' => '',
                'estado_conservacao' => 'desconhecido',
                'status' => 'rascunho',
                'visibilidade' => Visibilidade::Privado->value,
            ])
            ->assertRedirect(route('admin.fotografias.index'));

        $fotografia = ItemAcervo::where('titulo', 'Foto sem metadados opcionais')->firstOrFail();

        $this->assertNull($fotografia->legenda);
        $this->assertNull($fotografia->local_atual);
        $this->assertNull($fotografia->local_epoca);
        $this->assertNull($fotografia->evento);
        $this->assertNull($fotografia->cedente);
    }
}
