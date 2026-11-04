<?php

namespace App\Http\Requests\Admin;

use App\Models\Categoria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Override;

class UpdateCategoriaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', Categoria::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias', 'titulo')->ignore($this->categoria),
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
