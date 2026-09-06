<?php

namespace App\Http\Requests\Admin;

use App\Models\Categoria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Override;

class StoreCategoriaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Categoria::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255', 'unique:categorias,titulo'],
            'descricao' => ['nullable', 'string'],
        ];
    }

    #[Override]
    public function attributes()
    {
        return [
            'titulo' => 'título',
            'descricao' => 'descrição',
        ];
    }
}
