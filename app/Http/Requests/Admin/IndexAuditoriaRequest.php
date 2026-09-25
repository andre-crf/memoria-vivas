<?php

namespace App\Http\Requests\Admin;

use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Override;

class IndexAuditoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAudit', User::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $endDateRules = ['nullable', 'date_format:Y-m-d'];

        if ($this->filled('data_inicio')) {
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
                Rule::prohibitedIf(! $this->filled('entidade')),
            ],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'data_inicio.date_format' => 'A data inicial deve estar no formato válido.',
            'data_fim.date_format' => 'A data final deve estar no formato válido.',
            'data_fim.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'acao.enum' => 'A ação selecionada é inválida.',
            'entidade.enum' => 'A entidade selecionada é inválida.',
            'entidade_id.prohibited' => 'Selecione uma entidade antes de informar seu ID.',
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'data_inicio' => 'data inicial',
            'data_fim' => 'data final',
            'responsavel' => 'responsável',
            'acao' => 'ação',
            'entidade' => 'entidade',
            'entidade_id' => 'ID da entidade',
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
