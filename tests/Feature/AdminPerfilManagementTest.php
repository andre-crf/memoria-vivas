<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPerfilManagementTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role = 'operador', array $attributes = []): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
            ...$attributes,
        ]);
    }

    public function test_guest_is_redirected_from_all_profile_routes(): void
    {
        $this->get(route('admin.perfil.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.perfil.update'))->assertRedirect(route('login'));
        $this->put(route('admin.perfil.password.update'))->assertRedirect(route('login'));
    }

    public function test_inactive_user_cannot_access_profile(): void
    {
        $usuario = User::factory()->create(['role' => 'operador', 'status' => 'inativo']);

        $this->actingAs($usuario)
            ->get(route('admin.perfil.edit'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_active_admins_and_operators_can_view_only_their_own_profile(): void
    {
        foreach (['admin', 'operador'] as $role) {
            $usuario = $this->usuario($role, [
                'nome' => "Perfil {$role}",
                'email' => "{$role}@example.test",
            ]);
            $outroUsuario = $this->usuario('operador');

            $this->actingAs($usuario)
                ->get(route('admin.perfil.edit'))
                ->assertOk()
                ->assertSee($usuario->nome)
                ->assertSee($usuario->email)
                ->assertSee($role === 'admin' ? 'Administrador' : 'Operador')
                ->assertSee('Ativo')
                ->assertDontSee($outroUsuario->email)
                ->assertDontSee('name="role"', false)
                ->assertDontSee('name="status"', false);
        }
    }

    public function test_user_can_update_name_and_email_and_keep_their_current_email(): void
    {
        $usuario = $this->usuario('operador', ['email' => 'original@example.test']);

        $this->actingAs($usuario)
            ->put(route('admin.perfil.update'), [
                'nome' => 'Nome atualizado',
                'email' => 'atualizado@example.test',
            ])
            ->assertRedirect(route('admin.perfil.edit'))
            ->assertSessionHas('profile_success', 'Dados pessoais atualizados com sucesso.');

        $usuario->refresh();
        $this->assertSame('Nome atualizado', $usuario->nome);
        $this->assertSame('atualizado@example.test', $usuario->email);

        $this->actingAs($usuario)
            ->put(route('admin.perfil.update'), [
                'nome' => 'Mesmo e-mail',
                'email' => 'atualizado@example.test',
            ])
            ->assertRedirect(route('admin.perfil.edit'))
            ->assertSessionHasNoErrors();
    }

    public function test_personal_data_validation_uses_its_own_error_bag(): void
    {
        $usuario = $this->usuario();
        $outroUsuario = $this->usuario('admin', ['email' => 'existente@example.test']);

        $cases = [
            ['payload' => ['nome' => '', 'email' => $usuario->email], 'error' => 'nome'],
            ['payload' => ['nome' => str_repeat('a', 256), 'email' => $usuario->email], 'error' => 'nome'],
            ['payload' => ['nome' => 'Nome', 'email' => 'invalido'], 'error' => 'email'],
            ['payload' => ['nome' => 'Nome', 'email' => $outroUsuario->email], 'error' => 'email'],
        ];

        foreach ($cases as $case) {
            $this->actingAs($usuario)
                ->from(route('admin.perfil.edit'))
                ->put(route('admin.perfil.update'), $case['payload'])
                ->assertRedirect(route('admin.perfil.edit'))
                ->assertSessionHasErrors($case['error'], null, 'profileUpdate')
                ->assertSessionHas('errors', fn ($errors) => $errors->getBag('passwordUpdate')->count() === 0);
        }
    }

    public function test_each_form_displays_only_its_own_validation_feedback(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)
            ->from(route('admin.perfil.edit'))
            ->put(route('admin.perfil.update'), [
                'nome' => '',
                'email' => 'valor-preservado@example.test',
            ]);

        $this->get(route('admin.perfil.edit'))
            ->assertOk()
            ->assertSee('Os dados pessoais não foram atualizados')
            ->assertDontSee('A senha não foi atualizada')
            ->assertSee('value="valor-preservado@example.test"', false);

        $this->actingAs($usuario)
            ->from(route('admin.perfil.edit'))
            ->put(route('admin.perfil.password.update'), [
                'current_password' => 'incorreta',
                'password' => 'nova-senha',
                'password_confirmation' => 'nova-senha',
            ]);

        $this->get(route('admin.perfil.edit'))
            ->assertOk()
            ->assertSee('A senha não foi atualizada')
            ->assertDontSee('Os dados pessoais não foram atualizados')
            ->assertDontSee('value="nova-senha"', false);
    }

    public function test_personal_update_rejects_administrative_password_and_target_fields(): void
    {
        $usuario = $this->usuario('admin');
        $alvo = $this->usuario('operador');
        $hashOriginal = $usuario->password;

        $this->actingAs($usuario)
            ->put(route('admin.perfil.update'), [
                'nome' => 'Tentativa de alteração',
                'email' => 'tentativa@example.test',
                'role' => 'operador',
                'status' => 'inativo',
                'password' => 'nova-senha-indevida',
                'usuario_id' => $alvo->id,
            ])
            ->assertSessionHasErrors(['role', 'status', 'password', 'usuario_id'], null, 'profileUpdate');

        $usuario->refresh();
        $alvo->refresh();
        $this->assertNotSame('Tentativa de alteração', $usuario->nome);
        $this->assertSame('admin', $usuario->role);
        $this->assertSame('ativo', $usuario->status);
        $this->assertSame($hashOriginal, $usuario->password);
        $this->assertSame('operador', $alvo->role);
    }

    public function test_password_validation_uses_its_own_error_bag_and_preserves_hash(): void
    {
        $usuario = $this->usuario();
        $hashOriginal = $usuario->password;
        $cases = [
            ['payload' => ['current_password' => 'incorreta', 'password' => 'nova-senha', 'password_confirmation' => 'nova-senha'], 'error' => 'current_password'],
            ['payload' => ['current_password' => 'password', 'password' => 'curta', 'password_confirmation' => 'curta'], 'error' => 'password'],
            ['payload' => ['current_password' => 'password', 'password' => 'nova-senha', 'password_confirmation' => 'diferente'], 'error' => 'password'],
        ];

        foreach ($cases as $case) {
            $this->actingAs($usuario)
                ->from(route('admin.perfil.edit'))
                ->put(route('admin.perfil.password.update'), $case['payload'])
                ->assertRedirect(route('admin.perfil.edit'))
                ->assertSessionHasErrors($case['error'], null, 'passwordUpdate')
                ->assertSessionHas('errors', fn ($errors) => $errors->getBag('profileUpdate')->count() === 0);

            $this->assertSame($hashOriginal, $usuario->fresh()->password);
        }
    }

    public function test_valid_password_change_keeps_session_and_allows_future_login(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)
            ->put(route('admin.perfil.password.update'), [
                'current_password' => 'password',
                'password' => 'nova-senha-segura',
                'password_confirmation' => 'nova-senha-segura',
            ])
            ->assertRedirect(route('admin.perfil.edit'))
            ->assertSessionHas('password_success', 'Senha atualizada com sucesso.');

        $this->assertAuthenticatedAs($usuario);
        $this->assertTrue(Hash::check('nova-senha-segura', $usuario->fresh()->password));

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'email' => $usuario->email,
            'password' => 'nova-senha-segura',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_password_update_rejects_identity_administrative_and_target_fields(): void
    {
        $usuario = $this->usuario('admin');
        $alvo = $this->usuario();
        $hashOriginal = $usuario->password;

        $this->actingAs($usuario)
            ->put(route('admin.perfil.password.update'), [
                'current_password' => 'password',
                'password' => 'nova-senha-segura',
                'password_confirmation' => 'nova-senha-segura',
                'nome' => 'Nome indevido',
                'email' => 'indevido@example.test',
                'role' => 'operador',
                'status' => 'inativo',
                'usuario_id' => $alvo->id,
            ])
            ->assertSessionHasErrors(['nome', 'email', 'role', 'status', 'usuario_id'], null, 'passwordUpdate');

        $this->assertSame($hashOriginal, $usuario->fresh()->password);
        $this->assertSame('operador', $alvo->fresh()->role);
    }

    public function test_header_links_authenticated_user_name_to_profile_for_both_roles(): void
    {
        foreach (['admin', 'operador'] as $role) {
            $usuario = $this->usuario($role);

            $this->actingAs($usuario)
                ->get(route('admin.dashboard'))
                ->assertOk()
                ->assertSee('href="'.route('admin.perfil.edit').'"', false)
                ->assertSee('aria-label="Acessar meu perfil"', false);
        }
    }
}
