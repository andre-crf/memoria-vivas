<?php

namespace App\Auditing;

final readonly class AuditDiff
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  list<string>  $changedFields
     */
    private function __construct(
        public array $oldValues,
        public array $newValues,
        public array $changedFields,
    ) {}

    public static function between(AuditSnapshot $before, AuditSnapshot $after): self
    {
        $oldValues = [];
        $newValues = [];
        $changedFields = [];
        $fields = array_unique([
            ...array_keys($before->values),
            ...array_keys($after->values),
        ]);

        foreach ($fields as $field) {
            $oldValue = $before->values[$field] ?? null;
            $newValue = $after->values[$field] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $oldValues[$field] = $oldValue;
            $newValues[$field] = $newValue;
            $changedFields[] = $field;
        }

        return new self($oldValues, $newValues, $changedFields);
    }

    public function hasChanges(): bool
    {
        return $this->changedFields !== [];
    }
}
