<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Entrar');
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_root_url_is_the_public_home_for_guests_and_authenticated_users(): void
    {
        $this->get('/')->assertOk()->assertViewIs('public.home');

        $this
            ->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertViewIs('public.home');
    }

    public function test_footer_admin_link_leads_guests_to_login_and_back_to_the_dashboard(): void
    {
        User::factory()->create([
            'email' => 'admin@memorias.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'ativo',
        ]);

        $this->get('/')->assertSee('href="'.route('admin.dashboard').'"', false);

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $this
            ->post('/login', ['email' => 'admin@memorias.test', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_authenticated_user_opening_login_goes_to_the_dashboard(): void
    {
        $this
            ->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'ativo']))
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_active_internal_user_can_authenticate_and_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@memorias.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'ativo',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@memorias.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Administração do acervo');
    }

    public function test_active_operator_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'operador',
            'status' => 'ativo',
        ]);

        $this
            ->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Administração do acervo');
    }

    public function test_authenticated_user_without_internal_role_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->make([
            'role' => 'visitante',
            'status' => 'ativo',
        ]);

        $this
            ->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        User::factory()->create([
            'email' => 'inativo@memorias.test',
            'password' => 'password',
            'status' => 'inativo',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'inativo@memorias.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_inactive_user_is_removed_from_admin_area(): void
    {
        $user = User::factory()->create(['status' => 'inativo']);

        $response = $this
            ->actingAs($user)
            ->get('/admin');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertTrue(Auth::guest());
    }

    public function test_admin_area_redirects_to_login_after_logout(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this
            ->get('/admin')
            ->assertRedirect('/login');
    }
}
