<?php

namespace App\Http\Requests\Admin;

use App\Models\Assunto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Override;

class StoreAssuntoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Assunto::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255', 'unique:assuntos,titulo'],
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
