<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoriaRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    public function test_guest_is_redirected_from_create_category_form(): void
    {
        $this
            ->get(route('admin.categorias.create'))
            ->assertRedirect('/login');
    }

    public function test_admin_and_operator_can_access_create_category_form(): void
    {
        foreach (['admin', 'operador'] as $role) {
            $this
                ->actingAs($this->usuarioInterno($role))
                ->get(route('admin.categorias.create'))
                ->assertOk()
                ->assertSee('Nova categoria')
                ->assertSee('Título')
                ->assertSee('Descrição')
                ->assertSee('Cadastrar categoria');
        }
    }

    public function test_admin_can_register_category(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.categorias.store'), [
                'titulo' => 'Arquitetura',
                'descricao' => 'Fotografias relacionadas à arquitetura de Umuarama.',
            ])
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'titulo' => 'Arquitetura',
            'descricao' => 'Fotografias relacionadas à arquitetura de Umuarama.',
        ]);
    }

    public function test_operator_can_register_category(): void
    {
        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->post(route('admin.categorias.store'), [
                'titulo' => 'Eventos',
                'descricao' => 'Registros de eventos históricos.',
            ])
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'titulo' => 'Eventos',
            'descricao' => 'Registros de eventos históricos.',
        ]);
    }

    public function test_registration_validates_required_title(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.categorias.create'))
            ->post(route('admin.categorias.store'), [
                'descricao' => 'Categoria sem título.',
            ])
            ->assertRedirect(route('admin.categorias.create'))
            ->assertSessionHasErrors('titulo');

        $this->assertDatabaseCount('categorias', 0);
    }

    public function test_registration_validates_unique_title(): void
    {
        Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Categoria existente.',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.categorias.create'))
            ->post(route('admin.categorias.store'), [
                'titulo' => 'Arquitetura',
                'descricao' => 'Outra descrição.',
            ])
            ->assertRedirect(route('admin.categorias.create'))
            ->assertSessionHasErrors('titulo');

        $this->assertDatabaseCount('categorias', 1);
    }

    public function test_optional_description_can_be_empty(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.categorias.store'), [
                'titulo' => 'Sem descrição',
                'descricao' => '',
            ])
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'titulo' => 'Sem descrição',
            'descricao' => null,
        ]);
    }
}