<?php

namespace App\Services\Arquivos;

use App\Services\Arquivos\Contracts\ArquivoStorage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class LaravelArquivoStorage implements ArquivoStorage
{
    public function storeUploaded(string $provider, UploadedFile $file, string $directory, string $filename): string
    {
        $path = $this->disk($provider)->putFileAs($directory, $file, $filename);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Não foi possível armazenar o arquivo original.');
        }

        return $path;
    }

    public function makeDirectory(string $provider, string $directory): void
    {
        if (! $this->disk($provider)->makeDirectory($directory)) {
            throw new RuntimeException('Não foi possível preparar o diretório do arquivo.');
        }
    }

    public function absolutePath(string $provider, string $path): string
    {
        $disk = $this->disk($provider);

        if ($provider !== 'local') {
            throw new RuntimeException("O provider {$provider} não oferece caminho local para processamento.");
        }

        return $disk->path($path);
    }

    public function delete(string $provider, array $paths): bool
    {
        if ($paths === []) {
            return true;
        }

        return $this->disk($provider)->delete($paths);
    }

    private function disk(string $provider): FilesystemAdapter
    {
        $disk = config("acervo.storage_disks.{$provider}");

        if (! is_string($disk) || $disk === '') {
            throw new RuntimeException("O provider de arquivos {$provider} não está configurado.");
        }

        return Storage::disk($disk);
    }
}
