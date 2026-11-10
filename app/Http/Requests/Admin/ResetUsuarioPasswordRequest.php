<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Override;

class ResetUsuarioPasswordRequest extends FormRequest
{
    protected $errorBag = 'adminPasswordReset';

    public function authorize(): bool
    {
        return Gate::allows('resetPassword', $this->route('usuario'));
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
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
