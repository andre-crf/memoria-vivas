<?php

namespace App\Auditing;

use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;

final readonly class AuditEventData
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public AuditAction $action,
        public AuditEntity $subjectType,
        public int|string $subjectId,
        public ?string $subjectLabel = null,
        public ?array $oldValues = null,
        public ?array $newValues = null,
        public ?array $metadata = null,
    ) {}
}
