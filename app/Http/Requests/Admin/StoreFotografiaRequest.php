<?php

namespace App\Http\Requests\Admin;

use App\Enums\Visibilidade;
use App\Models\ItemAcervo;
use App\Support\DataHistorica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreFotografiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ItemAcervo::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'legenda' => ['nullable', 'string'],
            'local_atual' => ['nullable', 'string', 'max:255'],
            'local_epoca' => ['nullable', 'string', 'max:255'],
            'evento' => ['nullable', 'string', 'max:255'],
            'cedente' => ['nullable', 'string', 'max:255'],
            'estado_conservacao' => ['required', Rule::in(array_keys(ItemAcervo::ESTADOS_CONSERVACAO))],
            'status' => ['required', Rule::in(array_keys(ItemAcervo::STATUS))],
            'visibilidade' => ['required', Rule::enum(Visibilidade::class)],
            ...DataHistorica::regras(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'legenda' => 'legenda/descrição',
            'tipo_data' => 'nível de precisão da data',
            'local_atual' => 'local atual',
            'local_epoca' => 'local na época',
            'estado_conservacao' => 'estado de conservação',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $historicalDate = DataHistorica::deArray($validated)->paraPersistencia();

        $optionalFields = Arr::only($validated, [
            'legenda',
            'local_atual',
            'local_epoca',
            'evento',
            'cedente',
        ]);

        return [
            'tipo_item' => 'fotografia',
            'titulo' => $validated['titulo'],
            ...$this->emptyStringsToNull($optionalFields),
            ...$historicalDate,
            'estado_conservacao' => $validated['estado_conservacao'],
            'status' => $validated['status'],
            'visibilidade' => $validated['visibilidade'],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function emptyStringsToNull(array $values): array
    {
        return array_map(
            fn (mixed $value): mixed => $value === '' ? null : $value,
            $values,
        );
    }
}
