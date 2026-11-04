<?php

namespace App\Auditing;

final class AuditSanitizer
{
    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    public function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $sanitized = $this->sanitizeArray($values);

        return $sanitized === [] ? null : $sanitized;
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function sanitizeArray(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && $this->isSensitive($key)) {
                continue;
            }

            $sanitized[$key] = is_array($value)
                ? $this->sanitizeArray($value)
                : $value;
        }

        return $sanitized;
    }

    private function isSensitive(string $key): bool
    {
        $normalized = strtolower($key);
        $configured = array_map(
            static fn (mixed $field): string => strtolower((string) $field),
            config('audit.sensitive_fields', []),
        );

        return in_array($normalized, $configured, true)
            || str_contains($normalized, 'password')
            || str_ends_with($normalized, '_token')
            || str_ends_with($normalized, '_secret');
    }
}
