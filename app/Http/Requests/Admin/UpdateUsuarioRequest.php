<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Override;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('usuario'));
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('usuario')),
            ],
            'role' => ['sometimes', 'required', Rule::in(['admin', 'operador'])],
            'status' => ['sometimes', 'required', Rule::in(['ativo', 'inativo'])],
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'email' => 'e-mail',
            'role' => 'perfil',
            'status' => 'situação',
        ];
    }
}
