<?php

namespace App\Rules;

use App\Enums\TipoData;
use App\Support\DataHistorica;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Aplica as regras de {@see DataHistorica} sobre o conjunto dia/mês/ano/década.
 *
 * Deve ser anexada ao campo `tipo_data`, já que a consistência depende dos
 * demais campos da data. Uso em Livewire ou FormRequest:
 * `'tipo_data' => ['required', new DataHistoricaValida]`
 * (ou, mais simples, `DataHistorica::regras()`).
 */
class DataHistoricaValida implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof TipoData && TipoData::tryFrom((string) $value) === null) {
            return; // tipo inválido: a regra de enum já reporta.
        }

        $data = DataHistorica::deArray([
            'tipo_data' => $value,
            'dia' => $this->data['dia'] ?? null,
            'mes' => $this->data['mes'] ?? null,
            'ano' => $this->data['ano'] ?? null,
            'decada' => $this->data['decada'] ?? null,
        ]);

        foreach ($data->erros() as $erro) {
            $fail($erro);
        }
    }
}
