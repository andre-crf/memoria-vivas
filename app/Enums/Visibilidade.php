<?php

namespace App\Enums;

enum Visibilidade: string
{
    case Publico = 'publico';
    case Privado = 'privado';

    public function isPublico(): bool
    {
        return $this === self::Publico;
    }

    public function isPrivado(): bool
    {
        return $this === self::Privado;
    }

    public function label(): string
    {
        return match ($this) {
            self::Publico => 'Público',
            self::Privado => 'Privado',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
