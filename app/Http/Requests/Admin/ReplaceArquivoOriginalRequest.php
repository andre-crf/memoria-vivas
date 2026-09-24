<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesArquivoOriginal;
use App\Models\ItemAcervo;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceArquivoOriginalRequest extends FormRequest
{
    use ValidatesArquivoOriginal;

    public function authorize(): bool
    {
        $fotografia = $this->route('fotografia');

        return $fotografia instanceof ItemAcervo
            && ($this->user()?->can('update', $fotografia) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'arquivo_original' => $this->arquivoOriginalRules(required: true),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->arquivoOriginalMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'arquivo_original' => 'arquivo original',
        ];
    }
}
