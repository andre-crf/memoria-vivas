<?php

namespace Tests\Feature;

use App\Models\Assunto;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_links_are_displayed_in_every_admin_header(): void
    {
        $usuario = User::factory()->create([
            'role' => 'admin',
            'status' => 'ativo',
        ]);
        $categoria = Categoria::create(['titulo' => 'Arquitetura']);
        $assunto = Assunto::create(['titulo' => 'Patrimônio histórico']);

        $routes = [
            route('admin.dashboard'),
            route('admin.fotografias.index'),
            route('admin.fotografias.create'),
            route('admin.categorias.index'),
            route('admin.categorias.create'),
            route('admin.categorias.edit', $categoria),
            route('admin.assuntos.index'),
            route('admin.assuntos.create'),
            route('admin.assuntos.edit', $assunto),
        ];

        foreach ($routes as $route) {
            $this->actingAs($usuario)
                ->get($route)
                ->assertOk()
                ->assertSee(route('admin.categorias.index'))
                ->assertSee('Categorias')
                ->assertSee(route('admin.assuntos.index'))
                ->assertSee('Assuntos');
        }
    }
}
