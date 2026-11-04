<?php

namespace App\Auditing;

use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Models\User;
use Closure;
use Illuminate\Validation\Rule;

final class AuditEventFilterRules
{
    /** @var list<string> */
    public const FILTERS = [
        'data_inicio',
        'data_fim',
        'responsavel',
        'acao',
        'entidade',
        'entidade_id',
        'fuso',
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array<int, mixed>>
     */
    public function rules(array $filters): array
    {
        $endDateRules = ['nullable', 'date_format:Y-m-d'];

        if (filled($filters['data_inicio'] ?? null)) {
            $endDateRules[] = 'after_or_equal:data_inicio';
        }

        return [
            'data_inicio' => ['nullable', 'date_format:Y-m-d'],
            'data_fim' => $endDateRules,
            'responsavel' => ['nullable', $this->responsibleRule()],
            'acao' => ['nullable', Rule::enum(AuditAction::class)],
            'entidade' => ['nullable', Rule::enum(AuditEntity::class)],
            'entidade_id' => [
                'nullable',
                'string',
                'max:191',
                Rule::prohibitedIf(! filled($filters['entidade'] ?? null)),
            ],
            'fuso' => ['nullable', 'timezone'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'data_inicio.date_format' => 'A data inicial deve estar no formato válido.',
            'data_fim.date_format' => 'A data final deve estar no formato válido.',
            'data_fim.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'acao.enum' => 'A ação selecionada é inválida.',
            'entidade.enum' => 'A entidade selecionada é inválida.',
            'entidade_id.prohibited' => 'Selecione uma entidade antes de informar seu ID.',
            'fuso.timezone' => 'O fuso horário informado é inválido.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'data_inicio' => 'data inicial',
            'data_fim' => 'data final',
            'responsavel' => 'responsável',
            'acao' => 'ação',
            'entidade' => 'entidade',
            'entidade_id' => 'ID da entidade',
            'fuso' => 'fuso horário',
        ];
    }

    private function responsibleRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === 'system') {
                return;
            }

            if (! is_scalar($value) || ! ctype_digit((string) $value) || ! User::query()->whereKey($value)->exists()) {
                $fail('O responsável selecionado é inválido.');
            }
        };
    }
}
