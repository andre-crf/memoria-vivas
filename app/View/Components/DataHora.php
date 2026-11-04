<?php

namespace App\View\Components;

use App\Support\DataExibicao;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Exibe um instante no fuso de quem está vendo, mantendo em `datetime` o
 * instante canônico em UTC.
 */
class DataHora extends Component
{
    public function __construct(
        private readonly DataExibicao $datas,
        public DateTimeInterface|string|null $valor = null,
        public string $formato = 'completo',
        public string $vazio = 'Não registrado',
    ) {}

    public function texto(): string
    {
        return $this->datas->formatar($this->valor, $this->mascara()) ?? $this->vazio;
    }

    public function iso(): ?string
    {
        return $this->datas->isoUtc($this->valor);
    }

    public function render(): View
    {
        return view('components.data-hora');
    }

    private function mascara(): string
    {
        return $this->formato === 'curto'
            ? config('datas.formato_curto')
            : config('datas.formato_completo');
    }
}
