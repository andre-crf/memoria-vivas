<?php

namespace App\Support;

use App\Models\Arquivo;
use App\Models\ItemAcervo;
use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class OptimizedImageVersions
{
    public function generate(ItemAcervo $item, Arquivo $original): void
    {
        if (! $original->isImagem()) {
            return;
        }

        $sourcePath = Storage::disk('local')->path($original->storage_path);

        if (! is_file($sourcePath)) {
            Log::warning('Arquivo original não encontrado para gerar versões otimizadas.', [
                'arquivo_id' => $original->id,
                'storage_path' => $original->storage_path,
            ]);

            return;
        }

        foreach (config('acervo.optimized_versions') as $version => $settings) {
            if ($item->arquivos()->where('versao_arquivo', $version)->exists()) {
                continue;
            }

            try {
                $this->generateVersion($item, $original, $sourcePath, $version, (int) $settings['max_dimension']);
            } catch (Throwable $exception) {
                Log::warning('Falha ao gerar versão otimizada da fotografia.', [
                    'arquivo_id' => $original->id,
                    'item_acervo_id' => $item->id,
                    'versao_arquivo' => $version,
                    'erro' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function generateVersion(
        ItemAcervo $item,
        Arquivo $original,
        string $sourcePath,
        string $version,
        int $maxDimension,
    ): void {
        $source = $this->createSourceImage($sourcePath, $original->mime_type);

        if (! $source instanceof GdImage) {
            return;
        }

        try {
            [$sourceWidth, $sourceHeight] = $this->sourceDimensions($sourcePath);
            [$targetWidth, $targetHeight] = $this->targetDimensions($sourceWidth, $sourceHeight, $maxDimension);
            $target = imagecreatetruecolor($targetWidth, $targetHeight);

            if (! $target instanceof GdImage) {
                return;
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
                Storage::disk('local')->makeDirectory($directory);
                $storagePath = "{$directory}/".Str::uuid()->toString()."-{$version}.jpg";
                $absolutePath = Storage::disk('local')->path($storagePath);

                if (! imagejpeg($target, $absolutePath, 85)) {
                    throw new \RuntimeException('Não foi possível gravar a versão otimizada.');
                }

                $item->arquivos()->create([
                    'nome_original' => null,
                    'provider' => 'local',
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
            throw new \RuntimeException('Dimensões da imagem original não puderam ser lidas.');
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
