<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Override;

class UpdateAutorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->autor);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => [
                'required',
                Rule::in(['pessoa', 'instituicao']),
            ],
            'observacao' => ['nullable', 'string'],
        ];
    }

    #[Override]
    public function attributes()
    {
        return [
            'nome' => 'nome',
            'tipo' => 'tipo',
            'observacao' => 'observação',
        ];
    }
}
