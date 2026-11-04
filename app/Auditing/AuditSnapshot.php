<?php

namespace App\Auditing;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;
use UnitEnum;

final readonly class AuditSnapshot
{
    /**
     * @param  array<string, mixed>  $values
     */
    private function __construct(
        public array $values,
    ) {}

    /**
     * @param  list<string>  $fields
     */
    public static function fromModel(Model $model, array $fields): self
    {
        $values = [];

        foreach ($fields as $field) {
            $values[$field] = self::normalize($model->getAttribute($field));
        }

        return new self($values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function fromArray(array $values): self
    {
        return new self(self::normalizeArray($values));
    }

    private static function normalize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            $value instanceof Arrayable => self::normalizeArray($value->toArray()),
            $value instanceof JsonSerializable => self::normalize($value->jsonSerialize()),
            is_array($value) => self::normalizeArray($value),
            default => $value,
        };
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private static function normalizeArray(array $values): array
    {
        return array_map(
            static fn (mixed $value): mixed => self::normalize($value),
            $values,
        );
    }
}
