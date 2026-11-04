<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Override;

class UpdatePerfilPasswordRequest extends FormRequest
{
    protected $errorBag = 'passwordUpdate';

    public function authorize(): bool
    {
        return Gate::allows('updatePassword', $this->user());
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'nome' => ['prohibited'],
            'email' => ['prohibited'],
            'role' => ['prohibited'],
            'status' => ['prohibited'],
            'id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'usuario_id' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'A senha atual é obrigatória.',
            'current_password.current_password' => 'A senha atual está incorreta.',
            'password.required' => 'A nova senha é obrigatória.',
            'password.min' => 'A nova senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da nova senha não corresponde.',
            '*.prohibited' => 'O campo :attribute não pode ser alterado por esta funcionalidade.',
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'current_password' => 'senha atual',
            'password' => 'nova senha',
            'password_confirmation' => 'confirmação da nova senha',
            'nome' => 'nome',
            'email' => 'e-mail',
            'role' => 'perfil',
            'status' => 'situação',
            'id' => 'usuário',
            'user_id' => 'usuário',
            'usuario_id' => 'usuário',
        ];
    }
}
