<?php

namespace App\Support;

use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Services\Arquivos\Contracts\ArquivoStorage;
use App\Services\Arquivos\StorageMutationJournal;
use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OptimizedImageVersions
{
    public function __construct(
        private readonly ArquivoStorage $storage,
    ) {}

    public function generate(
        ItemAcervo $item,
        Arquivo $original,
        StorageMutationJournal $journal,
    ): OptimizedImageResult {
        if (! $original->isImagem()) {
            return new OptimizedImageResult(applicable: false);
        }

        $versions = config('acervo.optimized_versions', []);

        try {
            $sourcePath = $this->storage->absolutePath($original->provider, $original->storage_path);
        } catch (Throwable $exception) {
            Log::warning('Arquivo original não pôde ser preparado para gerar versões otimizadas.', [
                'arquivo_id' => $original->id,
                'provider' => $original->provider,
                'erro' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return new OptimizedImageResult(
                applicable: true,
                failedVersions: array_keys($versions),
            );
        }

        if (! is_file($sourcePath)) {
            Log::warning('Arquivo original não encontrado para gerar versões otimizadas.', [
                'arquivo_id' => $original->id,
                'storage_path' => $original->storage_path,
            ]);

            return new OptimizedImageResult(
                applicable: true,
                failedVersions: array_keys($versions),
            );
        }

        $generated = [];
        $failedVersions = [];

        foreach ($versions as $version => $settings) {
            if ($item->arquivos()->where('versao_arquivo', $version)->exists()) {
                continue;
            }

            try {
                $generated[] = $this->generateVersion(
                    $item,
                    $original,
                    $sourcePath,
                    $version,
                    (int) $settings['max_dimension'],
                    $journal,
                );
            } catch (Throwable $exception) {
                $failedVersions[] = $version;
                Log::warning('Falha ao gerar versão otimizada da fotografia.', [
                    'arquivo_id' => $original->id,
                    'item_acervo_id' => $item->id,
                    'versao_arquivo' => $version,
                    'erro' => $exception->getMessage(),
                    'exception' => $exception,
                ]);
            }
        }

        return new OptimizedImageResult(
            applicable: true,
            generated: $generated,
            failedVersions: $failedVersions,
        );
    }

    private function generateVersion(
        ItemAcervo $item,
        Arquivo $original,
        string $sourcePath,
        string $version,
        int $maxDimension,
        StorageMutationJournal $journal,
    ): Arquivo {
        $source = $this->createSourceImage($sourcePath, $original->mime_type);
        $storagePath = null;

        if (! $source instanceof GdImage) {
            throw new RuntimeException('O formato da imagem não pode ser processado.');
        }

        try {
            [$sourceWidth, $sourceHeight] = $this->sourceDimensions($sourcePath);
            [$targetWidth, $targetHeight] = $this->targetDimensions($sourceWidth, $sourceHeight, $maxDimension);
            $target = imagecreatetruecolor($targetWidth, $targetHeight);

            if (! $target instanceof GdImage) {
                throw new RuntimeException('Não foi possível preparar a versão otimizada.');
            }

            try {
                $white = imagecolorallocate($target, 255, 255, 255);
                imagefill($target, 0, 0, $white);
                imagecopyresampled(
                    $target,
                    $source,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $sourceWidth,
                    $sourceHeight,
                );

                $directory = "acervo/derivados/{$item->id}";
                $this->storage->makeDirectory($original->provider, $directory);
                $storagePath = "{$directory}/".Str::uuid()->toString()."-{$version}.jpg";
                $absolutePath = $this->storage->absolutePath($original->provider, $storagePath);
                $journal->created($original->provider, $storagePath);

                if (! imagejpeg($target, $absolutePath, 85)) {
                    throw new RuntimeException('Não foi possível gravar a versão otimizada.');
                }

                return $item->arquivos()->create([
                    'nome_original' => null,
                    'provider' => $original->provider,
                    'storage_path' => $storagePath,
                    'mime_type' => 'image/jpeg',
                    'file_size' => filesize($absolutePath),
                    'tipo_arquivo' => 'imagem',
                    'sha256' => hash_file('sha256', $absolutePath),
                    'versao_arquivo' => $version,
                    'width' => $targetWidth,
                    'height' => $targetHeight,
                ]);
            } finally {
                imagedestroy($target);
            }
        } catch (Throwable $exception) {
            if (is_string($storagePath)) {
                $journal->cleanupFailedCreation($original->provider, $storagePath);
            }

            throw $exception;
        } finally {
            imagedestroy($source);
        }
    }

    private function createSourceImage(string $sourcePath, string $mimeType): ?GdImage
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath) ?: null,
            'image/png' => imagecreatefrompng($sourcePath) ?: null,
            'image/webp' => imagecreatefromwebp($sourcePath) ?: null,
            default => null,
        };
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function sourceDimensions(string $sourcePath): array
    {
        $dimensions = getimagesize($sourcePath);

        if (! is_array($dimensions) || empty($dimensions[0]) || empty($dimensions[1])) {
            throw new RuntimeException('Dimensões da imagem original não puderam ser lidas.');
        }

        return [(int) $dimensions[0], (int) $dimensions[1]];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function targetDimensions(int $sourceWidth, int $sourceHeight, int $maxDimension): array
    {
        $largestSide = max($sourceWidth, $sourceHeight);

        if ($largestSide <= $maxDimension) {
            return [$sourceWidth, $sourceHeight];
        }

        $ratio = $maxDimension / $largestSide;

        return [
            max(1, (int) round($sourceWidth * $ratio)),
            max(1, (int) round($sourceHeight * $ratio)),
        ];
    }
}
