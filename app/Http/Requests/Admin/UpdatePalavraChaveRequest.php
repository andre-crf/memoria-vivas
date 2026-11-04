<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Override;

class UpdatePalavraChaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('palavraChave'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'termo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('palavras_chave', 'termo')->ignore($this->route('palavraChave')),
            ],
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'termo' => 'termo',
        ];
    }
}
