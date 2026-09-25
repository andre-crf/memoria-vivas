<?php

namespace App\Auditing;

use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use LogicException;

final class AuditEventCollector
{
    /** @var array<string, AuditEventData> */
    private array $events = [];

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function capture(
        AuditAction $action,
        AuditEntity $subjectType,
        int|string $subjectId,
        ?AuditSnapshot $before = null,
        ?AuditSnapshot $after = null,
        ?string $subjectLabel = null,
        ?array $metadata = null,
    ): void {
        $event = $this->makeEvent(
            action: $action,
            subjectType: $subjectType,
            subjectId: $subjectId,
            before: $before,
            after: $after,
            subjectLabel: $subjectLabel,
            metadata: $metadata,
        );

        if ($event === null) {
            return;
        }

        $key = $this->key($subjectType, $subjectId);
        $existing = $this->events[$key] ?? null;

        if ($existing === null) {
            $this->events[$key] = $event;

            return;
        }

        if ($existing->action !== $event->action) {
            throw new LogicException(
                'Uma entidade não pode receber ações de auditoria diferentes na mesma operação.',
            );
        }

        $merged = $this->merge($existing, $event);

        if ($merged === null) {
            unset($this->events[$key]);

            return;
        }

        $this->events[$key] = $merged;
    }

    /**
     * @return list<AuditEventData>
     */
    public function events(): array
    {
        return array_values($this->events);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function makeEvent(
        AuditAction $action,
        AuditEntity $subjectType,
        int|string $subjectId,
        ?AuditSnapshot $before,
        ?AuditSnapshot $after,
        ?string $subjectLabel,
        ?array $metadata,
    ): ?AuditEventData {
        return match ($action) {
            AuditAction::Created,
            AuditAction::Uploaded => $this->creationEvent(
                $action,
                $subjectType,
                $subjectId,
                $after,
                $subjectLabel,
                $metadata,
            ),
            AuditAction::Deleted,
            AuditAction::ForceDeleted => $this->deletionEvent(
                $action,
                $subjectType,
                $subjectId,
                $before,
                $subjectLabel,
                $metadata,
            ),
            AuditAction::PasswordChanged => new AuditEventData(
                action: $action,
                subjectType: $subjectType,
                subjectId: $subjectId,
                subjectLabel: $subjectLabel,
                metadata: $metadata,
            ),
            AuditAction::Updated,
            AuditAction::Restored,
            AuditAction::Activated,
            AuditAction::Deactivated,
            AuditAction::Replaced => $this->changeEvent(
                $action,
                $subjectType,
                $subjectId,
                $before,
                $after,
                $subjectLabel,
                $metadata,
            ),
        };
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function creationEvent(
        AuditAction $action,
        AuditEntity $subjectType,
        int|string $subjectId,
        ?AuditSnapshot $after,
        ?string $subjectLabel,
        ?array $metadata,
    ): ?AuditEventData {
        if ($after === null || $after->values === []) {
            return null;
        }

        return new AuditEventData(
            action: $action,
            subjectType: $subjectType,
            subjectId: $subjectId,
            subjectLabel: $subjectLabel,
            newValues: $after->values,
            metadata: $this->withChangedFields($metadata, array_keys($after->values)),
        );
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function deletionEvent(
        AuditAction $action,
        AuditEntity $subjectType,
        int|string $subjectId,
        ?AuditSnapshot $before,
        ?string $subjectLabel,
        ?array $metadata,
    ): ?AuditEventData {
        if ($before === null || $before->values === []) {
            return null;
        }

        return new AuditEventData(
            action: $action,
            subjectType: $subjectType,
            subjectId: $subjectId,
            subjectLabel: $subjectLabel,
            oldValues: $before->values,
            metadata: $this->withChangedFields($metadata, array_keys($before->values)),
        );
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function changeEvent(
        AuditAction $action,
        AuditEntity $subjectType,
        int|string $subjectId,
        ?AuditSnapshot $before,
        ?AuditSnapshot $after,
        ?string $subjectLabel,
        ?array $metadata,
    ): ?AuditEventData {
        if ($before === null || $after === null) {
            throw new LogicException("A ação {$action->value} exige snapshots anterior e novo.");
        }

        $diff = AuditDiff::between($before, $after);

        if (! $diff->hasChanges()) {
            return null;
        }

        return new AuditEventData(
            action: $action,
            subjectType: $subjectType,
            subjectId: $subjectId,
            subjectLabel: $subjectLabel,
            oldValues: $diff->oldValues,
            newValues: $diff->newValues,
            metadata: $this->withChangedFields($metadata, $diff->changedFields),
        );
    }

    private function merge(AuditEventData $existing, AuditEventData $next): ?AuditEventData
    {
        $oldValues = $this->mergeKeepingFirst($existing->oldValues, $next->oldValues);
        $newValues = $this->mergeKeepingLast($existing->newValues, $next->newValues);
        $metadata = array_replace_recursive($existing->metadata ?? [], $next->metadata ?? []);

        if ($this->isChangeAction($existing->action)) {
            $diff = AuditDiff::between(
                AuditSnapshot::fromArray($oldValues ?? []),
                AuditSnapshot::fromArray($newValues ?? []),
            );

            if (! $diff->hasChanges()) {
                return null;
            }

            $oldValues = $diff->oldValues;
            $newValues = $diff->newValues;
            $metadata = $this->withChangedFields($metadata, $diff->changedFields);
        } else {
            $metadata = $this->withChangedFields(
                $metadata,
                array_values(array_unique([
                    ...array_keys($oldValues ?? []),
                    ...array_keys($newValues ?? []),
                ])),
            );
        }

        return new AuditEventData(
            action: $existing->action,
            subjectType: $existing->subjectType,
            subjectId: $existing->subjectId,
            subjectLabel: $next->subjectLabel ?? $existing->subjectLabel,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: $metadata === [] ? null : $metadata,
        );
    }

    private function isChangeAction(AuditAction $action): bool
    {
        return in_array($action, [
            AuditAction::Updated,
            AuditAction::Restored,
            AuditAction::Activated,
            AuditAction::Deactivated,
            AuditAction::Replaced,
        ], true);
    }

    /**
     * @param  array<string, mixed>|null  $first
     * @param  array<string, mixed>|null  $next
     * @return array<string, mixed>|null
     */
    private function mergeKeepingFirst(?array $first, ?array $next): ?array
    {
        if ($first === null && $next === null) {
            return null;
        }

        return ($first ?? []) + ($next ?? []);
    }

    /**
     * @param  array<string, mixed>|null  $first
     * @param  array<string, mixed>|null  $next
     * @return array<string, mixed>|null
     */
    private function mergeKeepingLast(?array $first, ?array $next): ?array
    {
        if ($first === null && $next === null) {
            return null;
        }

        return array_replace($first ?? [], $next ?? []);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  list<string>  $changedFields
     * @return array<string, mixed>
     */
    private function withChangedFields(?array $metadata, array $changedFields): array
    {
        return array_replace($metadata ?? [], [
            'changed_fields' => array_values($changedFields),
        ]);
    }

    private function key(AuditEntity $subjectType, int|string $subjectId): string
    {
        return $subjectType->value.'|'.rawurlencode((string) $subjectId);
    }
}
