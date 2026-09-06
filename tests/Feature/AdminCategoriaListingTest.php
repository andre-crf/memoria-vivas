<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoriaListingTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    public function test_guest_is_redirected_from_category_listing(): void
    {
        $this
            ->get(route('admin.categorias.index'))
            ->assertRedirect('/login');
    }

    public function test_admin_and_operator_can_access_category_listing(): void
    {
        foreach (['admin', 'operador'] as $role) {
            $this
                ->actingAs($this->usuarioInterno($role))
                ->get(route('admin.categorias.index'))
                ->assertOk()
                ->assertSee('Categorias')
                ->assertSee('Nova categoria');
        }
    }

    public function test_category_listing_displays_categories(): void
    {
        $usuario = $this->usuarioInterno();

        Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Fotografias relacionadas à arquitetura de Umuarama.',
        ]);

        Categoria::create([
            'titulo' => 'Eventos',
            'descricao' => 'Registros de eventos históricos.',
        ]);

        $this
            ->actingAs($usuario)
            ->get(route('admin.categorias.index'))
            ->assertOk()
            ->assertSee('Arquitetura')
            ->assertSee('Fotografias relacionadas à arquitetura de Umuarama.')
            ->assertSee('Eventos')
            ->assertSee('Registros de eventos históricos.');
    }

    public function test_category_listing_displays_number_of_associated_items(): void
    {
        $usuario = $this->usuarioInterno();

        $categoria = Categoria::create([
            'titulo' => 'Eventos',
            'descricao' => 'Registros de eventos históricos.',
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
            ->actingAs($usuario)
            ->get(route('admin.categorias.index'))
            ->assertOk()
            ->assertSee('Eventos')
            ->assertSee('3');
    }

    public function test_category_listing_displays_empty_state(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.categorias.index'))
            ->assertOk()
            ->assertSee('Nenhuma categoria cadastrada');
    }
}