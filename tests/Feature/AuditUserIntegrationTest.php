<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\AuditContextFactory;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditSource;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Usuarios\CriarUsuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use JsonException;
use Tests\TestCase;

class AuditUserIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attributes = []): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'ativo',
            ...$attributes,
        ]);
    }

    private function operador(array $attributes = []): User
    {
        return User::factory()->create([
            'role' => 'operador',
            'status' => 'ativo',
            ...$attributes,
        ]);
    }

    public function test_administrative_creation_records_one_safe_event(): void
    {
        $admin = $this->admin(['nome' => 'Administradora']);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), [
                'nome' => 'Nova operadora',
                'email' => 'nova@example.test',
                'role' => 'operador',
                'password' => 'senha-inicial-segura',
                'password_confirmation' => 'senha-inicial-segura',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $usuario = User::where('email', 'nova@example.test')->firstOrFail();
        $event = AuditEvent::query()->firstOrFail();

        $this->assertDatabaseCount('audit_events', 1);
        $this->assertSame($admin->id, $event->actor_user_id);
        $this->assertSame('Administradora', $event->actor_name);
        $this->assertSame(AuditAction::Created, $event->action);
        $this->assertSame((string) $usuario->id, $event->subject_id);
        $this->assertSame('Nova operadora', $event->subject_label);
        $this->assertNull($event->old_values);
        $this->assertSame([
            'nome' => 'Nova operadora',
            'email' => 'nova@example.test',
            'role' => 'operador',
            'status' => 'ativo',
        ], $event->new_values);
        $this->assertSame('admin_user_create', $event->metadata['operation']);
        $this->assertSame(['nome', 'email', 'role', 'status'], $event->metadata['changed_fields']);
        $this->assertTrue(Str::isUuid($event->request_id));
        $this->assertSame($event->request_id, $event->correlation_id);

        $stored = DB::table('audit_events')->where('id', $event->id)->firstOrFail();
        $serialized = implode(' ', [
            (string) $stored->old_values,
            (string) $stored->new_values,
            (string) $stored->metadata,
        ]);

        $this->assertStringNotContainsString('senha-inicial-segura', $serialized);
        $this->assertStringNotContainsString($usuario->password, $serialized);
        $this->assertStringNotContainsString('remember_token', $serialized);
    }

    public function test_request_context_reuses_identifiers_and_authenticated_actor(): void
    {
        $admin = $this->admin([
            'nome' => 'Responsável pela operação',
            'role' => 'admin',
        ]);
        $request = Request::create('/admin/usuarios', 'POST');
        $request->setUserResolver(fn (): User => $admin);
        $factory = app(AuditContextFactory::class);

        $first = $factory->fromRequest($request);
        $second = $factory->fromRequest($request);

        $this->assertSame($admin->id, $first->actorUserId);
        $this->assertSame($admin->nome, $first->actorName);
        $this->assertSame($admin->role, $first->actorRole);
        $this->assertSame(AuditSource::Web, $first->source);
        $this->assertTrue(Str::isUuid($first->requestId));
        $this->assertSame($first->requestId, $first->correlationId);
        $this->assertSame($first->requestId, $second->requestId);
        $this->assertSame($first->correlationId, $second->correlationId);
    }

    public function test_combined_administrative_changes_generate_one_consolidated_event(): void
    {
        $admin = $this->admin();
        $usuario = $this->operador([
            'nome' => 'Nome anterior',
            'email' => 'anterior@example.test',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $usuario), [
                'nome' => 'Nome atualizado',
                'email' => 'atualizado@example.test',
                'role' => 'admin',
                'status' => 'inativo',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $event = AuditEvent::query()->firstOrFail();

        $this->assertDatabaseCount('audit_events', 1);
        $this->assertSame(AuditAction::Updated, $event->action);
        $this->assertSame([
            'nome' => 'Nome anterior',
            'email' => 'anterior@example.test',
            'role' => 'operador',
            'status' => 'ativo',
        ], $event->old_values);
        $this->assertSame([
            'nome' => 'Nome atualizado',
            'email' => 'atualizado@example.test',
            'role' => 'admin',
            'status' => 'inativo',
        ], $event->new_values);
        $this->assertSame(
            ['identity', 'authorization', 'status'],
            $event->metadata['change_groups'],
        );
        $this->assertSame('deactivated', $event->metadata['status_transition']);
    }

    public function test_isolated_status_changes_use_deactivation_and_activation_actions(): void
    {
        $admin = $this->admin();
        $usuario = $this->operador();

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $usuario), [
                'nome' => $usuario->nome,
                'email' => $usuario->email,
                'role' => $usuario->role,
                'status' => 'inativo',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $deactivation = AuditEvent::query()->firstOrFail();
        $this->assertSame(AuditAction::Deactivated, $deactivation->action);
        $this->assertSame(['status' => 'ativo'], $deactivation->old_values);
        $this->assertSame(['status' => 'inativo'], $deactivation->new_values);

        $usuario->refresh();
        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $usuario), [
                'nome' => $usuario->nome,
                'email' => $usuario->email,
                'role' => $usuario->role,
                'status' => 'ativo',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $activation = AuditEvent::query()->latest('id')->firstOrFail();
        $this->assertDatabaseCount('audit_events', 2);
        $this->assertSame(AuditAction::Activated, $activation->action);
        $this->assertSame(['status' => 'inativo'], $activation->old_values);
        $this->assertSame(['status' => 'ativo'], $activation->new_values);
    }

    public function test_unchanged_administrative_submission_does_not_generate_an_event(): void
    {
        $admin = $this->admin();
        $usuario = $this->operador();

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $usuario), [
                'nome' => $usuario->nome,
                'email' => $usuario->email,
                'role' => $usuario->role,
                'status' => $usuario->status,
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_rejected_last_admin_change_does_not_generate_an_event(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.usuarios.edit', $admin))
            ->put(route('admin.usuarios.update', $admin), [
                'nome' => $admin->nome,
                'email' => $admin->email,
                'role' => 'operador',
            ])
            ->assertRedirect(route('admin.usuarios.edit', $admin))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseCount('audit_events', 0);
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_profile_update_records_only_effective_identity_changes(): void
    {
        $usuario = $this->operador([
            'nome' => 'Nome anterior',
            'email' => 'perfil@example.test',
        ]);

        $this->actingAs($usuario)
            ->put(route('admin.perfil.update'), [
                'nome' => 'Nome no perfil',
                'email' => $usuario->email,
            ])
            ->assertRedirect(route('admin.perfil.edit'));

        $event = AuditEvent::query()->firstOrFail();
        $this->assertSame($usuario->id, $event->actor_user_id);
        $this->assertSame((string) $usuario->id, $event->subject_id);
        $this->assertSame('Nome anterior', $event->actor_name);
        $this->assertSame(AuditAction::Updated, $event->action);
        $this->assertSame(['nome' => 'Nome anterior'], $event->old_values);
        $this->assertSame(['nome' => 'Nome no perfil'], $event->new_values);
        $this->assertSame('self_profile_update', $event->metadata['operation']);
        $this->assertSame(['identity'], $event->metadata['change_groups']);
        $this->assertSame(['nome'], $event->metadata['changed_fields']);
    }

    public function test_unchanged_profile_submission_does_not_generate_an_event(): void
    {
        $usuario = $this->operador();

        $this->actingAs($usuario)
            ->put(route('admin.perfil.update'), [
                'nome' => $usuario->nome,
                'email' => $usuario->email,
            ])
            ->assertRedirect(route('admin.perfil.edit'));

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_password_change_records_an_event_without_passwords_or_hashes(): void
    {
        $usuario = $this->operador();
        $oldHash = $usuario->password;

        $this->actingAs($usuario)
            ->put(route('admin.perfil.password.update'), [
                'current_password' => 'password',
                'password' => 'nova-senha-segura',
                'password_confirmation' => 'nova-senha-segura',
            ])
            ->assertRedirect(route('admin.perfil.edit'));

        $usuario->refresh();
        $event = AuditEvent::query()->firstOrFail();

        $this->assertTrue(Hash::check('nova-senha-segura', $usuario->password));
        $this->assertSame(AuditAction::PasswordChanged, $event->action);
        $this->assertNull($event->old_values);
        $this->assertNull($event->new_values);
        $this->assertSame('self_password_change', $event->metadata['operation']);
        $this->assertSame('current_password_confirmation', $event->metadata['method']);

        $stored = DB::table('audit_events')->where('id', $event->id)->firstOrFail();
        $serialized = implode(' ', [
            (string) $stored->old_values,
            (string) $stored->new_values,
            (string) $stored->metadata,
        ]);

        $this->assertStringNotContainsString('nova-senha-segura', $serialized);
        $this->assertStringNotContainsString($oldHash, $serialized);
        $this->assertStringNotContainsString($usuario->password, $serialized);
    }

    public function test_same_password_and_invalid_current_password_do_not_generate_events(): void
    {
        $usuario = $this->operador();

        $this->actingAs($usuario)
            ->put(route('admin.perfil.password.update'), [
                'current_password' => 'password',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('admin.perfil.edit'));

        $this->assertDatabaseCount('audit_events', 0);

        $this->actingAs($usuario)
            ->from(route('admin.perfil.edit'))
            ->put(route('admin.perfil.password.update'), [
                'current_password' => 'incorreta',
                'password' => 'outra-senha-segura',
                'password_confirmation' => 'outra-senha-segura',
            ])
            ->assertRedirect(route('admin.perfil.edit'))
            ->assertSessionHasErrors('current_password', null, 'passwordUpdate');

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_audit_persistence_failure_rolls_back_user_creation(): void
    {
        $admin = $this->admin();
        $context = new AuditContext(
            actorUserId: $admin->id,
            actorName: $admin->nome,
            actorRole: $admin->role,
            source: AuditSource::Web,
            requestId: (string) Str::uuid(),
            correlationId: (string) Str::uuid(),
        );
        try {
            app(CriarUsuario::class)->execute($admin, [
                'nome' => "Usuário \xB1 inválido",
                'email' => 'rollback@example.test',
                'role' => 'operador',
                'password' => 'senha-segura',
            ], $context);

            $this->fail('A persistência da auditoria deveria falhar com JSON inválido.');
        } catch (JsonException) {
            // A falha na auditoria deve reverter também o usuário criado.
        }

        $this->assertDatabaseMissing('users', ['email' => 'rollback@example.test']);
        $this->assertDatabaseCount('audit_events', 0);
    }
}
