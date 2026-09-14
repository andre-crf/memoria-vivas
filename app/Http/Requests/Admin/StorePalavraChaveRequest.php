<?php

namespace App\Http\Requests\Admin;

use App\Models\PalavraChave;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Override;

class StorePalavraChaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', PalavraChave::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'termo' => ['required', 'string', 'max:255', 'unique:palavras_chave,termo'],
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
