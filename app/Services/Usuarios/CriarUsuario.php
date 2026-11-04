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

final readonly class CriarUsuario
{
    public function __construct(
        private AuditTransaction $auditTransaction,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, array $data, AuditContext $context): User
    {
        $context->assertActor($actor);

        return $this->auditTransaction->run(
            $context,
            function (AuditEventCollector $audit) use ($actor, $data): User {
                $actor = User::query()->lockForUpdate()->findOrFail($actor->getKey());
                Gate::forUser($actor)->authorize('create', User::class);

                $usuario = User::create([
                    'nome' => $data['nome'],
                    'email' => $data['email'],
                    'role' => $data['role'],
                    'password' => $data['password'],
                    'status' => 'ativo',
                ]);

                $audit->capture(
                    action: AuditAction::Created,
                    subjectType: AuditEntity::User,
                    subjectId: $usuario->id,
                    after: UserAuditSnapshot::capture($usuario),
                    subjectLabel: $usuario->nome,
                    metadata: ['operation' => 'admin_user_create'],
                );

                return $usuario;
            },
        );
    }
}
