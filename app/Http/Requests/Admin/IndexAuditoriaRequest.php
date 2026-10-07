<?php

namespace App\Http\Requests\Admin;

use App\Auditing\AuditEventFilterRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Override;

class IndexAuditoriaRequest extends FormRequest
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
        'page',
    ];

    public function authorize(): bool
    {
        return Gate::allows('viewAudit', User::class);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            ...app(AuditEventFilterRules::class)->rules($this->all()),
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            ...app(AuditEventFilterRules::class)->messages(),
            'page.integer' => 'A página informada é inválida.',
            'page.min' => 'A página informada é inválida.',
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return app(AuditEventFilterRules::class)->attributes();
    }
}
