<?php

namespace App\Queries\Publico;

final readonly class FiltrosCatalogoPublico
{
    public function __construct(
        public string $termo = '',
        public ?string $decada = null,
        public ?int $categoriaId = null,
        public ?int $assuntoId = null,
    ) {}

    public function possuiCriterios(): bool
    {
        return $this->termo !== '' || $this->possuiClassificacoes();
    }

    public function possuiClassificacoes(): bool
    {
        return $this->decada !== null
            || $this->categoriaId !== null
            || $this->assuntoId !== null;
    }

    /** @return array<string, string|int> */
    public function parametros(): array
    {
        return array_filter([
            'q' => $this->termo,
            'decada' => $this->decada,
            'categoria' => $this->categoriaId,
            'assunto' => $this->assuntoId,
        ], static fn (string|int|null $valor): bool => $valor !== null && $valor !== '');
    }

    /** @return array<string, string> */
    public function parametrosDaPesquisa(): array
    {
        return $this->termo === '' ? [] : ['q' => $this->termo];
    }
}
