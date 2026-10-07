<?php

namespace App\Http\Requests\Admin;

use App\Enums\Visibilidade;
use App\Http\Requests\Admin\Concerns\ValidatesArquivoOriginal;
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
    use ValidatesArquivoOriginal;

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
            'legenda' => ['nullable', 'string', 'max:5000'],
            'local_atual' => ['nullable', 'string', 'max:255'],
            'local_epoca' => ['nullable', 'string', 'max:255'],
            'evento' => ['nullable', 'string', 'max:255'],
            'cedente' => ['nullable', 'string', 'max:255'],
            'autor_id' => ['nullable', Rule::exists(Autor::class, 'id')],
            'arquivo_original' => $this->arquivoOriginalRules(),
            'categoria_ids' => ['nullable', 'array'],
            'categoria_ids.*' => ['integer', 'distinct', Rule::exists(Categoria::class, 'id')],
            'assunto_ids' => ['nullable', 'array'],
            'assunto_ids.*' => ['integer', 'distinct', Rule::exists(Assunto::class, 'id')],
            'palavra_chave_ids' => ['nullable', 'array'],
            'palavra_chave_ids.*' => ['integer', 'distinct', Rule::exists(PalavraChave::class, 'id')],
            'pessoa_ids' => ['nullable', 'array'],
            'pessoa_ids.*' => ['integer', 'distinct', Rule::exists(Pessoa::class, 'id')],
            // Compatibilidade com os nomes canônicos usados pelos serviços de acervo.
            'categorias' => ['sometimes', 'array'],
            'categorias.*' => ['integer', 'distinct', Rule::exists(Categoria::class, 'id')],
            'assuntos' => ['sometimes', 'array'],
            'assuntos.*' => ['integer', 'distinct', Rule::exists(Assunto::class, 'id')],
            'palavras_chave' => ['sometimes', 'array'],
            'palavras_chave.*' => ['integer', 'distinct', Rule::exists(PalavraChave::class, 'id')],
            'pessoas' => ['sometimes', 'array'],
            'pessoas.*' => ['integer', 'distinct', Rule::exists(Pessoa::class, 'id')],
            'classificacoes_enviadas' => ['sometimes', 'boolean'],
            'estado_conservacao' => ['required', Rule::in(array_keys(ItemAcervo::ESTADOS_CONSERVACAO))],
            'status' => ['required', Rule::in(array_keys(ItemAcervo::STATUS))],
            'visibilidade' => ['required', Rule::enum(Visibilidade::class)],
            ...DataHistorica::regras(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'legenda.max' => 'A legenda/descrição não pode ultrapassar 5.000 caracteres.',
            'dia.integer' => 'O dia deve ser um número inteiro válido.',
            'dia.between' => 'O dia deve estar entre 1 e 31.',
            'mes.integer' => 'O mês deve ser um número inteiro válido.',
            'mes.between' => 'O mês deve estar entre 1 e 12.',
            'ano.integer' => 'O ano deve ser um número inteiro válido.',
            'ano.between' => 'O ano deve estar entre '.DataHistorica::ANO_MINIMO.' e '.DataHistorica::anoMaximo().'.',
            'decada.regex' => 'A década deve ter quatro dígitos e terminar em zero, como 1980.',
            'tipo_data.enum' => 'Selecione um nível de precisão da data válido.',
            'status.in' => 'Selecione um status válido.',
            'visibilidade.enum' => 'Selecione uma visibilidade válida.',
            ...$this->arquivoOriginalMessages(),
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
            'dia' => 'dia',
            'mes' => 'mês',
            'ano' => 'ano',
            'decada' => 'década',
            'local_atual' => 'local atual',
            'local_epoca' => 'local na época',
            'autor_id' => 'autor',
            'arquivo_original' => 'arquivo original',
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
     * Traduz os nomes usados pelo formulário para os relacionamentos do model,
     * consumidos pelos serviços de aplicação e pelos snapshots de auditoria.
     *
     * @return array<string, array<int, int>>
     */
    public function classificationPayload(): array
    {
        $validated = $this->validated();
        $allRelationshipsSubmitted = (bool) ($validated['classificacoes_enviadas'] ?? false);
        $relationships = [];
        $fields = [
            'categorias' => 'categoria_ids',
            'assuntos' => 'assunto_ids',
            'palavras_chave' => 'palavra_chave_ids',
            'pessoas' => 'pessoa_ids',
        ];

        foreach ($fields as $relationship => $formField) {
            if (
                ! $allRelationshipsSubmitted
                && ! array_key_exists($formField, $validated)
                && ! array_key_exists($relationship, $validated)
            ) {
                continue;
            }

            $relationships[$relationship] = $this->integerList(
                $validated[$formField] ?? $validated[$relationship] ?? [],
            );
        }

        return $relationships;
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
