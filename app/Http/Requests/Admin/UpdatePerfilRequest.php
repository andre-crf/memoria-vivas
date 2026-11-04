<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Override;

class UpdatePerfilRequest extends FormRequest
{
    protected $errorBag = 'profileUpdate';

    public function authorize(): bool
    {
        return Gate::allows('updateIdentity', $this->user());
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
                Rule::unique('users', 'email')->ignore($this->user()),
            ],
            'role' => ['prohibited'],
            'status' => ['prohibited'],
            'password' => ['prohibited'],
            'id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'usuario_id' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'nome.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'email.unique' => 'Este e-mail já está em uso.',
            '*.prohibited' => 'O campo :attribute não pode ser alterado por esta funcionalidade.',
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
            'password' => 'senha',
            'id' => 'usuário',
            'user_id' => 'usuário',
            'usuario_id' => 'usuário',
        ];
    }
}
