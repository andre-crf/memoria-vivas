<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\RouteCollection;
use Tests\TestCase;

class StyleGuideAccessTest extends TestCase
{
    use RefreshDatabase;

    private const URL_ADMIN = '/admin/_style-guide';

    private const URL_PUBLICO = '/admin/_style-guide/publico';

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(self::URL_ADMIN)->assertRedirect(route('login'));
        $this->get(self::URL_PUBLICO)->assertRedirect(route('login'));
    }

    public function test_administrator_can_view_both_pages_in_testing_environment(): void
    {
        $usuario = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);

        $this->actingAs($usuario)
            ->get(self::URL_ADMIN)
            ->assertOk()
            ->assertSeeInOrder(['<h1', 'Style Guide', '</h1>'], false)
            ->assertSee('aria-label="Seções do Style Guide"', false)
            // Componentes reais: busca, coluna ordenável, paginação, dropzone e diálogo de confirmação do layout.
            ->assertSee('id="busca-q"', false)
            ->assertSee('aria-sort="ascending"', false)
            ->assertSee('aria-label="Paginação"', false)
            ->assertSee('x-data="fileDropzone(', false)
            ->assertSee('x-data="confirmationDialog"', false)
            // Componentes da Etapa 3.
            ->assertSee('id="cabecalho"', false)
            ->assertSee('id="badges"', false)
            ->assertSee('id="alertas"', false)
            ->assertSee('aria-busy="true"', false);

        $this->actingAs($usuario)
            ->get(self::URL_PUBLICO)
            ->assertOk()
            ->assertSee('public-shell', false)
            ->assertSee('Componentes do site público')
            ->assertSee('id="public-search"', false)
            ->assertSee('id="compartilhados"', false);
    }

    public function test_operator_is_forbidden(): void
    {
        $operador = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);

        $this->actingAs($operador)->get(self::URL_ADMIN)->assertForbidden();
        $this->actingAs($operador)->get(self::URL_PUBLICO)->assertForbidden();
    }

    public function test_inactive_administrator_is_logged_out(): void
    {
        $inativo = User::factory()->create(['role' => 'admin', 'status' => 'inativo']);

        $this->actingAs($inativo)->get(self::URL_ADMIN)->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_sorting_and_pagination_examples_follow_the_query_string(): void
    {
        $usuario = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);

        $this->actingAs($usuario)
            ->get(self::URL_ADMIN.'?ordem=data&direcao=desc&pagina=2')
            ->assertOk()
            ->assertSee('aria-sort="descending"', false)
            ->assertSee('Registro de exemplo 11')
            ->assertSee('aria-current="page"', false);
    }

    public function test_style_guide_is_not_linked_in_admin_navigation(): void
    {
        $usuario = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);

        $this->actingAs($usuario)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('_style-guide');
    }

    public function test_pages_return_not_found_outside_allowed_environments(): void
    {
        $usuario = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
        // Rotas já registradas (como num cache de rotas gerado fora de produção): o controller recusa.
        $this->app['env'] = 'production';

        $this->actingAs($usuario)->get(self::URL_ADMIN)->assertNotFound();
        $this->actingAs($usuario)->get(self::URL_PUBLICO)->assertNotFound();
    }

    public function test_routes_are_only_registered_in_allowed_environments(): void
    {
        $this->assertSame(['production' => false, 'staging' => false, 'local' => true, 'testing' => true, 'e2e' => true], [
            'production' => $this->routesRegisteredIn('production'),
            'staging' => $this->routesRegisteredIn('staging'),
            'local' => $this->routesRegisteredIn('local'),
            'testing' => $this->routesRegisteredIn('testing'),
            'e2e' => $this->routesRegisteredIn('e2e'),
        ]);
    }

    private function routesRegisteredIn(string $environment): bool
    {
        $this->app['env'] = $environment;
        $router = $this->app->make('router');
        $router->setRoutes(new RouteCollection);

        require base_path('routes/web.php');

        $router->getRoutes()->refreshNameLookups();
        $this->assertTrue($router->has('admin.dashboard'));

        return $router->has('admin.style-guide.admin') && $router->has('admin.style-guide.publico');
    }
}
