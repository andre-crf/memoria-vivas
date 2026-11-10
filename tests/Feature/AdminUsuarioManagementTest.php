<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsuarioManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $nome = 'Administrador'): User
    {
        return User::factory()->create(['nome' => $nome, 'role' => 'admin', 'status' => 'ativo']);
    }

    private function operador(string $nome = 'Operador'): User
    {
        return User::factory()->create(['nome' => $nome, 'role' => 'operador', 'status' => 'ativo']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'nome' => 'Novo usuário',
            'email' => 'novo@example.test',
            'role' => 'operador',
            'password' => 'Senha-Inicial-123',
            'password_confirmation' => 'Senha-Inicial-123',
        ], $overrides);
    }

    public function test_guest_and_operator_cannot_access_management_routes(): void
    {
        $target = $this->operador();
        $routes = [
            ['get', route('admin.usuarios.index')],
            ['get', route('admin.usuarios.create')],
            ['post', route('admin.usuarios.store')],
            ['get', route('admin.usuarios.edit', $target)],
            ['put', route('admin.usuarios.update', $target)],
            ['put', route('admin.usuarios.password.reset', $target)],
        ];

        foreach ($routes as [$method, $route]) {
            $this->{$method}($route)->assertRedirect('/login');
        }

        $operator = $this->operador('Segundo operador');
        foreach ($routes as [$method, $route]) {
            $this->actingAs($operator)->{$method}($route)->assertForbidden();
        }

        $this->actingAs($operator)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.usuarios.index'));
    }

    public function test_admin_sees_listing_order_fields_and_active_navigation(): void
    {
        $admin = $this->admin('Zélia');
        $this->operador('Antônio');

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSeeInOrder(['Antônio', 'Zélia'])
            ->assertSee('E-mail')
            ->assertSee('Perfil')
            ->assertSee('Situação')
            ->assertSee(route('admin.usuarios.index'))
            ->assertSee('aria-current="page"', false);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.create'))
            ->assertOk()
            ->assertSee('Cadastrar usuário')
            ->assertDontSee('name="status"', false);
    }

    public function test_creation_always_uses_active_status_and_hashes_initial_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), $this->payload(['status' => 'inativo']))
            ->assertRedirect(route('admin.usuarios.index'))
            ->assertSessionHas('success', 'Usuário cadastrado com sucesso.');

        $created = User::where('email', 'novo@example.test')->firstOrFail();
        $this->assertSame('ativo', $created->status);
        $this->assertSame('operador', $created->role);
        $this->assertNotSame('Senha-Inicial-123', $created->password);
        $this->assertTrue(Hash::check('Senha-Inicial-123', $created->password));
    }

    public function test_creation_validates_name_email_role_and_password(): void
    {
        $admin = $this->admin();
        $cases = [
            ['nome' => '', 'error' => 'nome'],
            ['nome' => str_repeat('a', 256), 'error' => 'nome'],
            ['email' => 'invalid', 'error' => 'email'],
            ['email' => $admin->email, 'error' => 'email'],
            ['role' => 'visitante', 'error' => 'role'],
            ['password' => 'short', 'password_confirmation' => 'short', 'error' => 'password'],
            ['password_confirmation' => 'diferente', 'error' => 'password'],
        ];

        foreach ($cases as $case) {
            $error = $case['error'];
            unset($case['error']);
            $this->actingAs($admin)
                ->post(route('admin.usuarios.store'), $this->payload($case))
                ->assertSessionHasErrors($error);
        }

        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_can_edit_other_user_role_and_status_without_changing_password(): void
    {
        $admin = $this->admin();
        $target = $this->operador();
        $oldHash = $target->password;

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $target), [
                'nome' => 'Operadora alterada',
                'email' => $target->email,
                'role' => 'admin',
                'status' => 'inativo',
                'password' => 'tentativa123',
            ])
            ->assertRedirect(route('admin.usuarios.index'))
            ->assertSessionHas('success', 'Usuário atualizado com sucesso.');

        $target->refresh();
        $this->assertSame('Operadora alterada', $target->nome);
        $this->assertSame('admin', $target->role);
        $this->assertSame('inativo', $target->status);
        $this->assertSame($oldHash, $target->password);
    }

    public function test_admin_can_inactivate_another_user_and_inactive_account_cannot_log_in(): void
    {
        $admin = $this->admin();
        $target = $this->operador();

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $target), [
                'nome' => $target->nome,
                'email' => $target->email,
                'role' => $target->role,
                'status' => 'inativo',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'inativo']);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'email' => $target->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_reactivate_another_user(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['role' => 'operador', 'status' => 'inativo']);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $target), [
                'nome' => $target->nome,
                'email' => $target->email,
                'role' => $target->role,
                'status' => 'ativo',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'ativo']);
    }

    public function test_admin_can_edit_own_name_and_email_but_not_role_or_status(): void
    {
        $admin = $this->admin();
        $this->admin('Outro administrador');
        $oldHash = $admin->password;

        $this->actingAs($admin)
            ->get(route('admin.usuarios.edit', $admin))
            ->assertOk()
            ->assertSee('Você não pode alterar seu próprio perfil ou situação')
            ->assertDontSee('name="role"', false)
            ->assertDontSee('name="status"', false);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $admin), [
                'nome' => 'Nome atualizado',
                'email' => 'atualizado@example.test',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $admin->refresh();
        $this->assertSame('Nome atualizado', $admin->nome);
        $this->assertSame('atualizado@example.test', $admin->email);
        $this->assertSame($oldHash, $admin->password);

        foreach ([['role' => 'operador'], ['status' => 'inativo']] as $change) {
            $this->actingAs($admin)
                ->from(route('admin.usuarios.edit', $admin))
                ->put(route('admin.usuarios.update', $admin), [
                    'nome' => 'Outra tentativa',
                    'email' => $admin->email,
                    ...$change,
                ])
                ->assertRedirect(route('admin.usuarios.edit', $admin))
                ->assertSessionHasErrors(array_key_first($change));
        }

        $admin->refresh();
        $this->assertSame('Nome atualizado', $admin->nome);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('ativo', $admin->status);
    }

    public function test_last_active_admin_cannot_be_downgraded_or_deactivated(): void
    {
        $actor = $this->admin();
        $target = $this->admin('Outro administrador');

        $this->actingAs($actor)
            ->put(route('admin.usuarios.update', $target), [
                'nome' => $target->nome,
                'email' => $target->email,
                'role' => 'operador',
                'status' => 'ativo',
            ])
            ->assertRedirect(route('admin.usuarios.index'));
        $target->refresh();
        $this->assertSame('operador', $target->role);

        // After the other administrator is downgraded, the actor is the last active admin.
        foreach ([['role' => 'operador', 'status' => 'ativo', 'error' => 'role'], ['role' => 'admin', 'status' => 'inativo', 'error' => 'status']] as $change) {
            $error = $change['error'];
            unset($change['error']);
            $this->actingAs($actor)
                ->from(route('admin.usuarios.edit', $actor))
                ->put(route('admin.usuarios.update', $actor), [
                    'nome' => 'Não alterar',
                    'email' => $actor->email,
                    ...$change,
                ])
                ->assertRedirect(route('admin.usuarios.edit', $actor))
                ->assertSessionHasErrors($error);
            $actor->refresh();
            $this->assertSame('admin', $actor->role);
            $this->assertSame('ativo', $actor->status);
            $this->assertSame('Administrador', $actor->nome);
        }
    }

    public function test_duplicate_email_and_invalid_edit_values_do_not_modify_user(): void
    {
        $admin = $this->admin();
        $target = $this->operador();
        $other = $this->operador('Outro operador');

        foreach ([
            ['email' => $other->email, 'error' => 'email'],
            ['nome' => str_repeat('a', 256), 'error' => 'nome'],
            ['role' => 'visitante', 'error' => 'role'],
            ['status' => 'bloqueado', 'error' => 'status'],
        ] as $change) {
            $error = $change['error'];
            unset($change['error']);
            $this->actingAs($admin)
                ->put(route('admin.usuarios.update', $target), [
                    'nome' => 'Tentativa',
                    'email' => $target->email,
                    'role' => $target->role,
                    'status' => $target->status,
                    ...$change,
                ])
                ->assertSessionHasErrors($error);
        }

        $target->refresh();
        $this->assertNotSame('Tentativa', $target->nome);
    }

    public function test_policy_allows_own_identity_and_password_but_no_third_party_password(): void
    {
        $admin = $this->admin();
        $operator = $this->operador();

        $this->assertTrue(Gate::forUser($operator)->allows('updateIdentity', $operator));
        $this->assertTrue(Gate::forUser($operator)->allows('updatePassword', $operator));
        $this->assertFalse(Gate::forUser($operator)->allows('updateIdentity', $admin));
        $this->assertFalse(Gate::forUser($admin)->allows('updatePassword', $operator));
        $this->assertTrue(Gate::forUser($admin)->allows('resetPassword', $operator));
        $this->assertFalse(Gate::forUser($admin)->allows('resetPassword', $admin));
        $this->assertFalse(Gate::forUser($operator)->allows('resetPassword', $admin));
        $this->assertFalse(Gate::forUser($admin)->allows('updateRole', [$admin, 'operador']));
        $this->assertFalse(Gate::forUser($admin)->allows('updateStatus', [$admin, 'inativo']));
    }

    public function test_admin_can_reset_another_users_password_without_changing_account_data(): void
    {
        $admin = $this->admin();
        $target = $this->operador();
        $oldHash = $target->password;
        $oldToken = $target->remember_token;

        $this->actingAs($admin)
            ->get(route('admin.usuarios.edit', $target))
            ->assertOk()
            ->assertSee('Redefinir senha')
            ->assertSee('name="password"', false);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.password.reset', $target), [
                'password' => 'Nova-Senha-Segura-1',
                'password_confirmation' => 'Nova-Senha-Segura-1',
            ])
            ->assertRedirect(route('admin.usuarios.edit', $target))
            ->assertSessionHas('password_success', 'Senha redefinida com sucesso.');

        $target->refresh();
        $this->assertNotSame($oldHash, $target->password);
        $this->assertNotSame($oldToken, $target->remember_token);
        $this->assertTrue(Hash::check('Nova-Senha-Segura-1', $target->password));
        $this->assertSame('operador', $target->role);
        $this->assertSame('ativo', $target->status);

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email' => $target->email, 'password' => 'Nova-Senha-Segura-1'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_reset_rejects_invalid_password_extra_fields_and_own_account(): void
    {
        $admin = $this->admin();
        $target = $this->operador();
        $oldHash = $target->password;

        $this->actingAs($admin)
            ->from(route('admin.usuarios.edit', $target))
            ->put(route('admin.usuarios.password.reset', $target), [
                'password' => 'curta',
                'password_confirmation' => 'diferente',
                'role' => 'admin',
            ])
            ->assertRedirect(route('admin.usuarios.edit', $target))
            ->assertSessionHasErrors(['password', 'role'], null, 'adminPasswordReset');

        $this->assertSame($oldHash, $target->fresh()->password);
        $this->assertSame('operador', $target->fresh()->role);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.edit', $admin))
            ->assertDontSee(route('admin.usuarios.password.reset', $admin));
        $this->actingAs($admin)
            ->put(route('admin.usuarios.password.reset', $admin), [
                'password' => 'Nova-Senha-Segura-1',
                'password_confirmation' => 'Nova-Senha-Segura-1',
            ])
            ->assertForbidden();
    }

    public function test_reset_revokes_existing_database_sessions_of_target(): void
    {
        config()->set('session.driver', 'database');
        $admin = $this->admin();
        $target = $this->operador();
        $sessionId = str_repeat('a', 40);
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Teste',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->actingAs($admin)->put(route('admin.usuarios.password.reset', $target), [
            'password' => 'Nova-Senha-Segura-1',
            'password_confirmation' => 'Nova-Senha-Segura-1',
        ])->assertRedirect(route('admin.usuarios.edit', $target));

        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
    }
}
