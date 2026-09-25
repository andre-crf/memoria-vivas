<?php

namespace App\Services\Arquivos\Contracts;

use Illuminate\Http\UploadedFile;

interface ArquivoStorage
{
    public function storeUploaded(string $provider, UploadedFile $file, string $directory, string $filename): string;

    public function makeDirectory(string $provider, string $directory): void;

    public function absolutePath(string $provider, string $path): string;

    /** @param list<string> $paths */
    public function delete(string $provider, array $paths): bool;
}
