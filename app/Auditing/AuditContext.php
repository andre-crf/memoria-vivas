<?php

namespace App\Auditing;

use App\Auditing\Enums\AuditSource;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class AuditContext
{
    public function __construct(
        public ?int $actorUserId,
        public ?string $actorName,
        public ?string $actorRole,
        public AuditSource $source,
        public string $requestId,
        public string $correlationId,
    ) {
        if (! Str::isUuid($requestId)) {
            throw new InvalidArgumentException('O request_id da auditoria deve ser um UUID válido.');
        }

        if (! Str::isUuid($correlationId)) {
            throw new InvalidArgumentException('O correlation_id da auditoria deve ser um UUID válido.');
        }
    }

    public static function forUser(
        User $user,
        AuditSource $source,
        string $requestId,
        ?string $correlationId = null,
    ): self {
        return new self(
            actorUserId: (int) $user->getKey(),
            actorName: $user->nome,
            actorRole: $user->role,
            source: $source,
            requestId: $requestId,
            correlationId: $correlationId ?? $requestId,
        );
    }

    public static function forSystem(
        AuditSource $source,
        string $requestId,
        ?string $correlationId = null,
    ): self {
        return new self(
            actorUserId: null,
            actorName: null,
            actorRole: null,
            source: $source,
            requestId: $requestId,
            correlationId: $correlationId ?? $requestId,
        );
    }
}
