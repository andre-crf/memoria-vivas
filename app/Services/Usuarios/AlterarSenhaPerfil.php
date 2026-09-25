<?php

namespace App\Services\Usuarios;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventCollector;
use App\Auditing\AuditTransaction;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

final readonly class AlterarSenhaPerfil
{
    public function __construct(
        private AuditTransaction $auditTransaction,
    ) {}

    public function execute(User $usuario, string $password, AuditContext $context): User
    {
        $context->assertActor($usuario);

        return $this->auditTransaction->run(
            $context,
            function (AuditEventCollector $audit) use ($usuario, $password): User {
                $usuario = User::query()->lockForUpdate()->findOrFail($usuario->getKey());
                Gate::forUser($usuario)->authorize('updatePassword', $usuario);

                if (Hash::check($password, $usuario->password)) {
                    return $usuario;
                }

                $usuario->update(['password' => $password]);
                $audit->capture(
                    action: AuditAction::PasswordChanged,
                    subjectType: AuditEntity::User,
                    subjectId: $usuario->id,
                    subjectLabel: $usuario->nome,
                    metadata: [
                        'operation' => 'self_password_change',
                        'method' => 'current_password_confirmation',
                    ],
                );

                return $usuario;
            },
        );
    }
}
