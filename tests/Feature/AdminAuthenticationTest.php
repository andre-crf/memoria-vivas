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

    public function test_home_redirects_guest_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_home_redirects_authenticated_user_to_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/');

        $response->assertRedirect(route('admin.dashboard'));
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
}
