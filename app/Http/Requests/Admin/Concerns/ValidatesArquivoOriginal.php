<?php

namespace App\Http\Requests\Admin\Concerns;

trait ValidatesArquivoOriginal
{
    /**
     * @return array<int, mixed>
     */
    protected function arquivoOriginalRules(bool $required = false): array
    {
        $originalUpload = config('acervo.uploads.original');

        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimetypes:'.implode(',', $originalUpload['mime_types']),
            'extensions:'.implode(',', $originalUpload['extensions']),
            'max:'.$originalUpload['max_kb'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function arquivoOriginalMessages(): array
    {
        $originalUpload = config('acervo.uploads.original');

        return [
            'arquivo_original.mimetypes' => 'O arquivo original deve ser uma imagem ou PDF compatível.',
            'arquivo_original.extensions' => 'O arquivo original deve usar uma das extensões aceitas: '.implode(', ', $originalUpload['extensions']).'.',
            'arquivo_original.max' => 'O arquivo original não pode passar de '.$this->formattedMaxUploadSize((int) $originalUpload['max_kb']).'.',
        ];
    }

    private function formattedMaxUploadSize(int $maxKilobytes): string
    {
        if ($maxKilobytes >= 1024 && $maxKilobytes % 1024 === 0) {
            return ($maxKilobytes / 1024).' MB';
        }

        return $maxKilobytes.' KB';
    }
}
