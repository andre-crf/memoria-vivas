<?php

namespace App\Auditing;

use App\Models\AuditEvent;
use Illuminate\Support\Facades\DB;
use JsonException;

final readonly class AuditRecorder
{
    public function __construct(
        private AuditSanitizer $sanitizer,
    ) {}

    /**
     * @throws JsonException
     */
    public function record(AuditContext $context, AuditEventData $event): AuditEvent
    {
        $id = DB::table(AuditEvent::TABLE)->insertGetId([
            'actor_user_id' => $context->actorUserId,
            'actor_name' => $context->actorName,
            'actor_role' => $context->actorRole,
            'action' => $event->action->value,
            'subject_type' => $event->subjectType->value,
            'subject_id' => (string) $event->subjectId,
            'subject_label' => $event->subjectLabel,
            'old_values' => $this->encode($this->sanitizer->sanitize($event->oldValues)),
            'new_values' => $this->encode($this->sanitizer->sanitize($event->newValues)),
            'metadata' => $this->encode($this->sanitizer->sanitize($event->metadata)),
            'request_id' => $context->requestId,
            'correlation_id' => $context->correlationId,
            'source' => $context->source->value,
            'occurred_at' => now(),
        ]);

        return AuditEvent::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>|null  $values
     *
     * @throws JsonException
     */
    private function encode(?array $values): ?string
    {
        return $values === null
            ? null
            : json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
