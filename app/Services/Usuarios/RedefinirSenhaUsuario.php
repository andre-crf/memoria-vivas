<?php

namespace App\Services\Usuarios;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventCollector;
use App\Auditing\AuditTransaction;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final readonly class RedefinirSenhaUsuario
{
    public function __construct(
        private AuditTransaction $auditTransaction,
    ) {}

    public function execute(User $actor, User $usuario, string $password, AuditContext $context): User
    {
        $context->assertActor($actor);

        return $this->auditTransaction->run(
            $context,
            function (AuditEventCollector $audit) use ($actor, $usuario, $password): User {
                $actor = User::query()->lockForUpdate()->findOrFail($actor->getKey());
                $usuario = User::query()->lockForUpdate()->findOrFail($usuario->getKey());
                Gate::forUser($actor)->authorize('resetPassword', $usuario);

                $usuario->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))
                        ->where('user_id', $usuario->getKey())
                        ->delete();
                }

                $audit->capture(
                    action: AuditAction::PasswordChanged,
                    subjectType: AuditEntity::User,
                    subjectId: $usuario->id,
                    subjectLabel: $usuario->nome,
                    metadata: ['operation' => 'admin_password_reset'],
                );

                return $usuario;
            },
        );
    }
}
