<?php

namespace App\Queries\Admin;

final readonly class FiltrosFotografiasAdmin
{
    public function __construct(
        public string $termo = '',
        public ?string $status = null,
        public ?string $visibilidade = null,
    ) {}

    public function possuiCriterios(): bool
    {
        return $this->parametros() !== [];
    }

    /** @return array<string, string> */
    public function parametros(): array
    {
        return array_filter([
            'q' => $this->termo,
            'status' => $this->status,
            'visibilidade' => $this->visibilidade,
        ], static fn (?string $valor): bool => $valor !== null && $valor !== '');
    }
}
