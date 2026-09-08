<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Override;

class UpdateAssuntoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('assunto'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assuntos', 'titulo')->ignore($this->route('assunto')),
            ],
            'descricao' => ['nullable', 'string'],
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'descricao' => 'descrição',
        ];
    }
}
