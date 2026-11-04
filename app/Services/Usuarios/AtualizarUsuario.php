<?php

namespace App\Services\Usuarios;

use App\Auditing\AuditContext;
use App\Auditing\AuditDiff;
use App\Auditing\AuditEventCollector;
use App\Auditing\AuditSnapshot;
use App\Auditing\AuditTransaction;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\UserAuditSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class AtualizarUsuario
{
    public function __construct(
        private AuditTransaction $auditTransaction,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, User $usuario, array $data, AuditContext $context): User
    {
        $context->assertActor($actor);

        return $this->auditTransaction->run(
            $context,
            function (AuditEventCollector $audit) use ($actor, $usuario, $data): User {
                User::query()
                    ->where('role', 'admin')
                    ->where('status', 'ativo')
                    ->lockForUpdate()
                    ->get();

                $actor = User::query()->lockForUpdate()->findOrFail($actor->getKey());
                $usuario = User::query()->lockForUpdate()->findOrFail($usuario->getKey());
                Gate::forUser($actor)->authorize('update', $usuario);

                $role = $data['role'] ?? $usuario->role;
                $status = $data['status'] ?? $usuario->status;

                $this->authorizeRoleChange($actor, $usuario, $role);
                $this->authorizeStatusChange($actor, $usuario, $status);

                $before = UserAuditSnapshot::capture($usuario);
                $usuario->fill([
                    'nome' => $data['nome'],
                    'email' => $data['email'],
                    'role' => $role,
                    'status' => $status,
                ]);

                if ($usuario->isDirty(UserAuditSnapshot::FIELDS)) {
                    $usuario->save();
                }

                $after = UserAuditSnapshot::capture($usuario);
                $diff = AuditDiff::between($before, $after);
                $action = $this->actionFor($diff, $after);

                $audit->capture(
                    action: $action,
                    subjectType: AuditEntity::User,
                    subjectId: $usuario->id,
                    before: $before,
                    after: $after,
                    subjectLabel: $usuario->nome,
                    metadata: $this->metadataFor($diff, $after),
                );

                return $usuario;
            },
        );
    }

    private function authorizeRoleChange(User $actor, User $usuario, string $role): void
    {
        if ($role === $usuario->role || Gate::forUser($actor)->allows('updateRole', [$usuario, $role])) {
            return;
        }

        throw ValidationException::withMessages([
            'role' => $actor->is($usuario)
                ? 'Você não pode alterar seu próprio perfil.'
                : 'O último administrador ativo não pode perder o perfil de administrador.',
        ]);
    }

    private function authorizeStatusChange(User $actor, User $usuario, string $status): void
    {
        if ($status === $usuario->status || Gate::forUser($actor)->allows('updateStatus', [$usuario, $status])) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => $actor->is($usuario)
                ? 'Você não pode alterar sua própria situação.'
                : 'O último administrador ativo não pode ser inativado.',
        ]);
    }

    private function actionFor(AuditDiff $diff, AuditSnapshot $after): AuditAction
    {
        if ($diff->changedFields !== ['status']) {
            return AuditAction::Updated;
        }

        return $after->values['status'] === 'ativo'
            ? AuditAction::Activated
            : AuditAction::Deactivated;
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataFor(AuditDiff $diff, AuditSnapshot $after): array
    {
        $groups = [];

        if (array_intersect($diff->changedFields, ['nome', 'email']) !== []) {
            $groups[] = 'identity';
        }

        if (in_array('role', $diff->changedFields, true)) {
            $groups[] = 'authorization';
        }

        if (in_array('status', $diff->changedFields, true)) {
            $groups[] = 'status';
        }

        $metadata = [
            'operation' => 'admin_user_update',
            'change_groups' => $groups,
        ];

        if (in_array('status', $diff->changedFields, true)) {
            $metadata['status_transition'] = $after->values['status'] === 'ativo'
                ? 'activated'
                : 'deactivated';
        }

        return $metadata;
    }
}
