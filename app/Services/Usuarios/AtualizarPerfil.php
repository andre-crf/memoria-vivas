<?php

namespace App\Services\Usuarios;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventCollector;
use App\Auditing\AuditTransaction;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\UserAuditSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final readonly class AtualizarPerfil
{
    public function __construct(
        private AuditTransaction $auditTransaction,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $usuario, array $data, AuditContext $context): User
    {
        $context->assertActor($usuario);

        return $this->auditTransaction->run(
            $context,
            function (AuditEventCollector $audit) use ($usuario, $data): User {
                $usuario = User::query()->lockForUpdate()->findOrFail($usuario->getKey());
                Gate::forUser($usuario)->authorize('updateIdentity', $usuario);

                $before = UserAuditSnapshot::capture($usuario);
                $usuario->fill([
                    'nome' => $data['nome'],
                    'email' => $data['email'],
                ]);

                if ($usuario->isDirty(['nome', 'email'])) {
                    $usuario->save();
                }

                $after = UserAuditSnapshot::capture($usuario);
                $audit->capture(
                    action: AuditAction::Updated,
                    subjectType: AuditEntity::User,
                    subjectId: $usuario->id,
                    before: $before,
                    after: $after,
                    subjectLabel: $usuario->nome,
                    metadata: [
                        'operation' => 'self_profile_update',
                        'change_groups' => ['identity'],
                    ],
                );

                return $usuario;
            },
        );
    }
}
