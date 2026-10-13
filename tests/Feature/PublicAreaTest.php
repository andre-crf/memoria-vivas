<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_is_available_without_authentication(): void
    {
        $this
            ->get(route('public.home'))
            ->assertOk()
            ->assertSee('Memórias Vivas de Umuarama')
            ->assertSee('images/memorias-vivas-logo.jpg', false)
            ->assertSee('Navegação pública', false)
            ->assertSee('Buscar no catálogo')
            ->assertSee(route('public.catalogo'), false)
            ->assertSee('Área administrativa')
            ->assertDontSee('Administração do acervo');
    }

    public function test_authenticated_user_can_access_public_home_without_being_redirected(): void
    {
        $user = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);

        $this
            ->actingAs($user)
            ->get(route('public.home'))
            ->assertOk();
    }

    public function test_public_catalog_accepts_search_term_without_authentication(): void
    {
        $this
            ->get(route('public.catalogo', ['q' => 'praça']))
            ->assertOk()
            ->assertSee('Busca por “praça”.')
            ->assertSee('value="praça"', false)
            ->assertSee('Nenhum item público disponível ainda');
    }

    public function test_unknown_page_shows_public_navigation_and_recovery_links(): void
    {
        $this
            ->get('/publico/pagina-inexistente')
            ->assertNotFound()
            ->assertSee('Esta página não foi encontrada')
            ->assertSee(route('public.home'), false)
            ->assertSee(route('public.catalogo'), false);
    }
}
