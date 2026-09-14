<?php

namespace App\Http\Requests\Admin;

use App\Models\Pessoa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Override;

class StorePessoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Pessoa::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'observacao' => ['nullable', 'string'],
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'observacao' => 'observação',
        ];
    }
}
