<?php

namespace App\Http\Requests\Admin;

use App\Enums\Visibilidade;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
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
            'autor_id' => ['nullable', Rule::exists(Autor::class, 'id')],
            'categoria_ids' => ['nullable', 'array'],
            'categoria_ids.*' => ['integer', 'distinct', Rule::exists(Categoria::class, 'id')],
            'assunto_ids' => ['nullable', 'array'],
            'assunto_ids.*' => ['integer', 'distinct', Rule::exists(Assunto::class, 'id')],
            'palavra_chave_ids' => ['nullable', 'array'],
            'palavra_chave_ids.*' => ['integer', 'distinct', Rule::exists(PalavraChave::class, 'id')],
            'pessoa_ids' => ['nullable', 'array'],
            'pessoa_ids.*' => ['integer', 'distinct', Rule::exists(Pessoa::class, 'id')],
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
            'autor_id' => 'autor',
            'categoria_ids' => 'categorias',
            'categoria_ids.*' => 'categoria',
            'assunto_ids' => 'assuntos',
            'assunto_ids.*' => 'assunto',
            'palavra_chave_ids' => 'palavras-chave',
            'palavra_chave_ids.*' => 'palavra-chave',
            'pessoa_ids' => 'pessoas identificadas',
            'pessoa_ids.*' => 'pessoa identificada',
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
            'autor_id',
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
     * @return array{categoria_ids: array<int, int>, assunto_ids: array<int, int>, palavra_chave_ids: array<int, int>, pessoa_ids: array<int, int>}
     */
    public function classificationPayload(): array
    {
        $validated = $this->validated();

        return [
            'categoria_ids' => $this->integerList($validated['categoria_ids'] ?? []),
            'assunto_ids' => $this->integerList($validated['assunto_ids'] ?? []),
            'palavra_chave_ids' => $this->integerList($validated['palavra_chave_ids'] ?? []),
            'pessoa_ids' => $this->integerList($validated['pessoa_ids'] ?? []),
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

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, int>
     */
    private function integerList(array $values): array
    {
        return array_values(array_map(
            fn (mixed $value): int => (int) $value,
            $values,
        ));
    }
}
