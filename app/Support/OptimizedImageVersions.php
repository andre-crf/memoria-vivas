<?php

namespace App\Support;

use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Services\Arquivos\Contracts\ArquivoStorage;
use App\Services\Arquivos\StorageMutationJournal;
use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Imagick;
use RuntimeException;
use Throwable;

/**
 * Gera as versões otimizadas (thumbnail, medium, large) a partir do original.
 *
 * O original é lido uma única vez, já com a orientação corrigida, em sRGB e sem
 * metadados; cada versão é reduzida de um clone dessa mesma imagem. Uma versão que
 * teria exatamente as dimensões de uma versão menor é dispensada: a menor basta.
 *
 * O Imagick é o motor principal; sem ele, o GD cobre JPEG, PNG e WebP.
 */
class OptimizedImageVersions
{
    private const OUTPUT_FORMATS = [
        'webp' => ['extension' => 'webp', 'mime_type' => 'image/webp'],
        'jpeg' => ['extension' => 'jpg', 'mime_type' => 'image/jpeg'],
    ];

    public function __construct(
        private readonly ArquivoStorage $storage,
    ) {}

    /**
     * Gera e registra as versões que o item ainda não tem.
     */
    public function generate(
        ItemAcervo $item,
        Arquivo $original,
        StorageMutationJournal $journal,
    ): OptimizedImageResult {
        if (! $original->isImagem()) {
            return new OptimizedImageResult(applicable: false);
        }

        $missing = array_values(array_filter(
            $this->versions(),
            fn (string $version): bool => ! $item->arquivos()->where('versao_arquivo', $version)->exists(),
        ));
        ['files' => $files, 'failed' => $failedVersions, 'dispensed' => $dispensed] = $this->render($item, $original, $missing, $journal);
        $generated = [];

        foreach ($files as $version => $attributes) {
            try {
                $generated[] = $item->arquivos()->create($attributes);
            } catch (Throwable $exception) {
                $journal->cleanupFailedCreation($attributes['provider'], $attributes['storage_path']);
                $failedVersions[] = $version;
                $this->logFailure($item, $original, $version, 'registro', $exception);
            }
        }

        return new OptimizedImageResult(
            applicable: true,
            generated: $generated,
            failedVersions: $failedVersions,
            dispensedVersions: $dispensed,
        );
    }

    /**
     * Nomes das versões configuradas, na ordem da configuração.
     *
     * @return list<string>
     */
    public function versions(): array
    {
        return array_keys(config('acervo.optimized_versions', []));
    }

    /**
     * Dimensões de cada versão para um original, e as versões dispensadas por repetirem
     * as dimensões de uma versão menor (mapeadas para a versão que as substitui).
     *
     * @return array{produce: array<string, array{0: int, 1: int}>, dispensed: array<string, string>}
     */
    public function plan(int $width, int $height): array
    {
        $versions = collect(config('acervo.optimized_versions', []))
            ->map(fn (array $settings): int => (int) ($settings['max_dimension'] ?? 0))
            ->sort();
        $produce = [];
        $dispensed = [];
        $previous = null;

        foreach ($versions as $version => $maxDimension) {
            $dimensions = $this->targetDimensions($width, $height, max(1, $maxDimension));

            if ($previous !== null && $produce[$previous] === $dimensions) {
                $dispensed[$version] = $previous;

                continue;
            }

            $produce[$version] = $dimensions;
            $previous = $version;
        }

        return ['produce' => $produce, 'dispensed' => $dispensed];
    }

    /**
     * Versões que o original deve ter. Sem dimensões registradas, todas são esperadas.
     *
     * @return list<string>
     */
    public function expectedVersions(Arquivo $original): array
    {
        if (! $original->width || ! $original->height) {
            return $this->versions();
        }

        $produce = $this->plan($original->width, $original->height)['produce'];

        return array_values(array_filter($this->versions(), fn (string $version): bool => isset($produce[$version])));
    }

    /**
     * Grava os arquivos das versões pedidas sem registrá-los no banco.
     *
     * O original é lido uma vez; cada versão é reduzida de um clone dessa mesma imagem
     * decodificada. Cada arquivo gravado entra no journal como criado; uma versão que
     * falha é limpa na hora e listada em `failed`, sem impedir as demais. `metrics` traz
     * os tempos de leitura e de cada versão, usados pela medição do processamento.
     *
     * @param  list<string>  $versions
     * @return array{files: array<string, array<string, mixed>>, failed: list<string>, dispensed: list<string>, metrics: array<string, mixed>}
     */
    public function render(
        ItemAcervo $item,
        Arquivo $original,
        array $versions,
        StorageMutationJournal $journal,
    ): array {
        $metrics = ['engine' => null, 'color' => null, 'read_ms' => 0.0, 'versions_ms' => []];

        if ($versions === []) {
            return ['files' => [], 'failed' => [], 'dispensed' => [], 'metrics' => $metrics];
        }

        $stage = 'configuracao';
        $startedAt = hrtime(true);

        try {
            $output = $this->outputSettings();
            $metrics['engine'] = $this->engine();
            $stage = 'leitura';
            $sourcePath = $this->sourcePath($original);
            [$source, $metrics['color']] = $metrics['engine'] === 'imagick'
                ? $this->openWithImagick($sourcePath, $item, $original, $stage)
                : [$this->openWithGd($sourcePath, $original->mime_type), 'gd'];
        } catch (Throwable $exception) {
            Log::warning('Arquivo original não pôde ser preparado para gerar versões otimizadas.', [
                ...$this->logContext($item, $original),
                'etapa' => $stage,
                'erro' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return ['files' => [], 'failed' => $versions, 'dispensed' => [], 'metrics' => $metrics];
        }

        $metrics['read_ms'] = $this->elapsedMs($startedAt);
        $plan = $this->plan(...$this->dimensionsOf($source));
        $dispensed = array_values(array_intersect($versions, array_keys($plan['dispensed'])));
        $produce = array_intersect_key($plan['produce'], array_flip($versions));
        $files = [];
        $failed = [];

        try {
            foreach ($produce as $version => [$width, $height]) {
                $startedAt = hrtime(true);
                $stage = 'redimensionamento';
                $resized = null;

                try {
                    $resized = $this->resized($source, $width, $height);
                    $stage = 'gravacao';
                    $files[$version] = $this->write($item, $original, $resized, $version, $output, $journal);
                } catch (Throwable $exception) {
                    $failed[] = $version;
                    $this->logFailure($item, $original, $version, $stage, $exception);
                } finally {
                    // O clone é descartado logo; a próxima versão parte de novo do original decodificado.
                    if ($resized !== null && $resized !== $source) {
                        $this->destroy($resized);
                    }
                }

                $metrics['versions_ms'][$version] = $this->elapsedMs($startedAt);
            }
        } finally {
            $this->destroy($source);
        }

        return [
            'files' => $files,
            'failed' => array_values(array_intersect($versions, $failed)),
            'dispensed' => $dispensed,
            'metrics' => $metrics,
        ];
    }

    /**
     * @return array{format: string, extension: string, mime_type: string, quality: int}
     */
    private function outputSettings(): array
    {
        $format = strtolower((string) config('acervo.optimized_output.format', 'webp'));
        $quality = (int) config('acervo.optimized_output.quality', 80);

        if (! isset(self::OUTPUT_FORMATS[$format])) {
            throw new RuntimeException("Formato de versão otimizada não suportado: {$format}.");
        }

        if ($quality < 1 || $quality > 100) {
            throw new RuntimeException('A qualidade das versões otimizadas deve estar entre 1 e 100.');
        }

        return ['format' => $format, 'quality' => $quality, ...self::OUTPUT_FORMATS[$format]];
    }

    private function engine(): string
    {
        $engine = strtolower((string) config('acervo.image_processing.engine', 'auto'));

        return match (true) {
            $engine === 'gd' => 'gd',
            $engine === 'imagick' && ! extension_loaded('imagick') => throw new RuntimeException('A extensão Imagick foi configurada, mas não está disponível.'),
            $engine === 'imagick', $engine === 'auto' && extension_loaded('imagick') => 'imagick',
            $engine === 'auto' => 'gd',
            default => throw new RuntimeException("Motor de processamento de imagens desconhecido: {$engine}."),
        };
    }

    private function sourcePath(Arquivo $original): string
    {
        $sourcePath = $this->storage->absolutePath($original->provider, $original->storage_path);

        if (! is_file($sourcePath)) {
            throw new RuntimeException('Arquivo original não encontrado no armazenamento.');
        }

        return $sourcePath;
    }

    /**
     * Lê a primeira página (TIFF) ou quadro (WebP animado) já pronta para a web.
     *
     * @return array{0: Imagick, 1: string} a imagem e como as cores foram tratadas
     */
    private function openWithImagick(string $sourcePath, ItemAcervo $item, Arquivo $original, string &$stage): array
    {
        $this->applyImagickResourceLimits();
        $image = new Imagick;

        try {
            $image->readImage($sourcePath.'[0]');
            $stage = 'orientacao';
            $image->autoOrient();
            $stage = 'cor';
            $color = $this->convertToSrgb($image, $item, $original);
            $stage = 'metadados';
            // Remove EXIF (incluindo GPS), IPTC, XMP e o perfil ICC, já aplicado aos pixels.
            $image->stripImage();
            $image->setImagePage(0, 0, 0, 0);

            return [$image, $color];
        } catch (Throwable $exception) {
            $image->clear();

            throw $exception;
        }
    }

    /**
     * Converte para sRGB. Com perfil embutido, usa o perfil sRGB do servidor; se ele faltar
     * ou falhar, registra o motivo e faz a conversão aproximada pelo espaço de cor.
     *
     * @return string profile, approximate ou none
     */
    private function convertToSrgb(Imagick $image, ItemAcervo $item, Arquivo $original): string
    {
        if ($image->getImageProfiles('icc', false) !== []) {
            $profilePath = (string) config('acervo.image_processing.srgb_profile');

            try {
                $profile = $profilePath !== '' && is_readable($profilePath) ? file_get_contents($profilePath) : false;

                if (! is_string($profile) || $profile === '') {
                    throw new RuntimeException('O arquivo do perfil sRGB não existe ou não pode ser lido.');
                }

                // Todo perfil ICC traz a assinatura "acsp" no cabeçalho; sem ela, o ImageMagick ignoraria o arquivo calado.
                if (strlen($profile) < 128 || substr($profile, 36, 4) !== 'acsp') {
                    throw new RuntimeException('O arquivo do perfil sRGB não é um perfil ICC válido.');
                }

                $image->profileImage('icc', $profile);

                return 'profile';
            } catch (Throwable $exception) {
                Log::warning('Perfil sRGB indisponível; as cores serão convertidas de forma aproximada.', [
                    ...$this->logContext($item, $original),
                    'etapa' => 'cor',
                    'perfil' => $profilePath,
                    'erro' => $exception->getMessage(),
                ]);
            }
        }

        if (in_array($image->getImageColorspace(), [Imagick::COLORSPACE_SRGB, Imagick::COLORSPACE_GRAY], true)) {
            return 'none';
        }

        if (! $image->transformImageColorspace(Imagick::COLORSPACE_SRGB)) {
            throw new RuntimeException('Não foi possível converter as cores do original para sRGB.');
        }

        return 'approximate';
    }

    private function openWithGd(string $sourcePath, string $mimeType): GdImage
    {
        // Sem o GD nenhuma versão pode ser gerada; a mensagem diz isso em vez de um "função indefinida".
        if (! extension_loaded('gd')) {
            throw new RuntimeException('A extensão GD é necessária para gerar versões otimizadas.');
        }

        $source = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath) ?: null,
            'image/png' => imagecreatefrompng($sourcePath) ?: null,
            'image/webp' => imagecreatefromwebp($sourcePath) ?: null,
            'image/tiff', 'image/x-tiff' => throw new RuntimeException('A extensão Imagick é necessária para gerar versões de imagens TIFF.'),
            default => null,
        };

        if (! $source instanceof GdImage) {
            throw new RuntimeException('O formato da imagem não pode ser processado.');
        }

        return $this->orientGd($source, $sourcePath);
    }

    /**
     * Aplica a orientação EXIF no GD quando a extensão exif está disponível.
     */
    private function orientGd(GdImage $image, string $sourcePath): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($sourcePath)['Orientation'] ?? 1);
        return $this->orientGdByExifOrientation($image, $orientation);
    }

    private function orientGdByExifOrientation(GdImage $image, int $orientation): GdImage
    {
        $rotated = match ($orientation) {
            2 => $this->flipGd($image, IMG_FLIP_HORIZONTAL),
            3 => $this->rotateGd($image, 180),
            4 => $this->flipGd($image, IMG_FLIP_VERTICAL),
            5 => $this->rotateGd($this->flipGd($image, IMG_FLIP_HORIZONTAL), 90),
            6 => $this->rotateGd($image, -90),
            7 => $this->flipGd($this->rotateGd($image, -90), IMG_FLIP_VERTICAL),
            8 => $this->rotateGd($image, 90),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    private function rotateGd(GdImage $image, int $angle): GdImage
    {
        $rotated = imagerotate($image, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : throw new RuntimeException('Não foi possível aplicar a orientação EXIF.');
    }

    private function flipGd(GdImage $image, int $mode): GdImage
    {
        return imageflip($image, $mode) ? $image : throw new RuntimeException('Não foi possível aplicar a orientação EXIF.');
    }

    private function applyImagickResourceLimits(): void
    {
        $limits = [
            Imagick::RESOURCETYPE_MEMORY => 128 * 1024 * 1024,
            Imagick::RESOURCETYPE_MAP => 256 * 1024 * 1024,
            Imagick::RESOURCETYPE_DISK => 1024 * 1024 * 1024,
            Imagick::RESOURCETYPE_AREA => 16_000_000,
        ];

        foreach ($limits as $resource => $maximum) {
            $current = Imagick::getResourceLimit($resource);
            $limit = $current > 0 ? min($current, $maximum) : $maximum;

            if (! Imagick::setResourceLimit($resource, $limit)) {
                throw new RuntimeException("Não foi possível aplicar o limite de recurso do Imagick ({$resource}).");
            }
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function dimensionsOf(Imagick|GdImage $image): array
    {
        return $image instanceof Imagick
            ? [$image->getImageWidth(), $image->getImageHeight()]
            : [imagesx($image), imagesy($image)];
    }

    private function resized(Imagick|GdImage $image, int $width, int $height): Imagick|GdImage
    {
        if ($this->dimensionsOf($image) === [$width, $height]) {
            return $image;
        }

        if ($image instanceof Imagick) {
            $resized = clone $image;
            $resized->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);

            return $resized;
        }

        $resized = imagecreatetruecolor($width, $height);

        if (! $resized instanceof GdImage) {
            throw new RuntimeException('Não foi possível preparar a versão otimizada.');
        }

        // Mantém a transparência; o JPEG, que não tem canal alfa, recebe fundo branco ao gravar.
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $resized;
    }

    /**
     * @param  array{format: string, extension: string, mime_type: string, quality: int}  $output
     * @return array<string, mixed>
     */
    private function write(
        ItemAcervo $item,
        Arquivo $original,
        Imagick|GdImage $image,
        string $version,
        array $output,
        StorageMutationJournal $journal,
    ): array {
        $directory = "acervo/derivados/{$item->id}";
        $this->storage->makeDirectory($original->provider, $directory);
        $storagePath = "{$directory}/".Str::uuid()->toString()."-{$version}.{$output['extension']}";
        $absolutePath = $this->storage->absolutePath($original->provider, $storagePath);
        $journal->created($original->provider, $storagePath);

        try {
            $image instanceof Imagick
                ? $this->writeWithImagick($image, $absolutePath, $output)
                : $this->writeWithGd($image, $absolutePath, $output);

            [$width, $height] = $this->dimensionsOf($image);

            return [
                'nome_original' => null,
                'provider' => $original->provider,
                'storage_path' => $storagePath,
                'mime_type' => $output['mime_type'],
                'file_size' => filesize($absolutePath),
                'tipo_arquivo' => 'imagem',
                'sha256' => hash_file('sha256', $absolutePath),
                'versao_arquivo' => $version,
                'width' => $width,
                'height' => $height,
            ];
        } catch (Throwable $exception) {
            $journal->cleanupFailedCreation($original->provider, $storagePath);

            throw $exception;
        }
    }

    /**
     * @param  array{format: string, quality: int}  $output
     */
    private function writeWithImagick(Imagick $image, string $absolutePath, array $output): void
    {
        $frame = clone $image;

        try {
            if ($output['format'] === 'jpeg') {
                $frame->setImageBackgroundColor('white');
                $frame->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            }

            $frame->setImageFormat($output['format']);
            $frame->setImageCompressionQuality($output['quality']);
            $frame->setImageDepth(8);

            if (! $frame->writeImage($absolutePath)) {
                throw new RuntimeException('Não foi possível gravar a versão otimizada.');
            }
        } finally {
            $frame->clear();
        }
    }

    /**
     * @param  array{format: string, quality: int}  $output
     */
    private function writeWithGd(GdImage $image, string $absolutePath, array $output): void
    {
        if ($output['format'] === 'webp') {
            imagesavealpha($image, true);
            $written = imagewebp($image, $absolutePath, $output['quality']);
        } else {
            $flattened = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($flattened, 0, 0, imagecolorallocate($flattened, 255, 255, 255));
            imagecopy($flattened, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            $written = imagejpeg($flattened, $absolutePath, $output['quality']);
        }

        if (! $written) {
            throw new RuntimeException('Não foi possível gravar a versão otimizada.');
        }
    }

    // O GD libera a memória sozinho quando o objeto sai de escopo; o Imagick precisa do clear().
    private function destroy(Imagick|GdImage $image): void
    {
        if ($image instanceof Imagick) {
            $image->clear();
        }
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

    private function logFailure(ItemAcervo $item, Arquivo $original, string $version, string $stage, Throwable $exception): void
    {
        Log::warning('Falha ao gerar versão otimizada da fotografia.', [
            ...$this->logContext($item, $original),
            'versao_arquivo' => $version,
            'etapa' => $stage,
            'erro' => $exception->getMessage(),
            'exception' => $exception,
        ]);
    }

    /**
     * Identificação do original nos logs; os logs são internos e nunca chegam ao público.
     *
     * @return array<string, mixed>
     */
    private function logContext(ItemAcervo $item, Arquivo $original): array
    {
        return [
            'item_acervo_id' => $item->id,
            'arquivo_id' => $original->id,
            'mime_type' => $original->mime_type,
            'provider' => $original->provider,
        ];
    }

    private function elapsedMs(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 1);
    }
}
