<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Override;

class UpdatePessoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('pessoa'));
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
