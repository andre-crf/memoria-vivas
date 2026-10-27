<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditSource;
use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\AuditEvent;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Services\Arquivos\GerenciarArquivoOriginal;
use App\Services\Arquivos\RegenerarVersoesOtimizadas;
use App\Support\OptimizedImageResult;
use App\Support\OptimizedImageVersions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Processamento das versões otimizadas: formatos de entrada e saída, dimensões,
 * versões redundantes, transparência, orientação, cor, metadados e falhas.
 */
class OptimizedImageVersionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('imagick') || ! extension_loaded('gd')) {
            $this->markTestSkipped('Execute no Docker, com Imagick e GD, para validar o processamento de imagens.');
        }

        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        config([
            'acervo.optimized_output.format' => 'webp',
            'acervo.image_processing.srgb_profile' => '/usr/share/color/icc/sRGB.icc',
        ]);

        parent::tearDown();
    }

    public function test_plan_keeps_only_the_smallest_of_versions_with_identical_dimensions(): void
    {
        $versions = app(OptimizedImageVersions::class);

        $this->assertSame(
            ['produce' => ['thumbnail' => [320, 213], 'medium' => [1024, 683], 'large' => [1920, 1280]], 'dispensed' => []],
            $versions->plan(2400, 1600),
        );
        $this->assertSame(
            ['produce' => ['thumbnail' => [320, 240], 'medium' => [800, 600]], 'dispensed' => ['large' => 'medium']],
            $versions->plan(800, 600),
        );
        $this->assertSame(
            ['produce' => ['thumbnail' => [300, 200]], 'dispensed' => ['medium' => 'thumbnail', 'large' => 'thumbnail']],
            $versions->plan(300, 200),
        );
        // Exatamente no limite da média: a grande seria igual.
        $this->assertSame(['large' => 'medium'], $versions->plan(1024, 512)['dispensed']);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function formatosDeEntrada(): array
    {
        return [
            'JPEG' => ['jpeg', 'image/jpeg'],
            'PNG' => ['png', 'image/png'],
            'WebP' => ['webp', 'image/webp'],
            'TIFF' => ['tiff', 'image/tiff'],
        ];
    }

    #[DataProvider('formatosDeEntrada')]
    public function test_every_supported_input_format_becomes_webp_with_the_expected_dimensions(string $formato, string $mime): void
    {
        [$item, $original] = $this->itemWithOriginal($this->photo(2000, 1500, $formato), $mime, 2000, 1500);
        $originalBytes = Storage::disk('local')->get($original->storage_path);

        $result = $this->generate($item, $original);

        $this->assertSame([], $result->failedVersions);
        $this->assertSame([], $result->dispensedVersions);
        $derivadas = $this->derivatives($item);
        $this->assertSame(
            ['large' => [1920, 1440], 'medium' => [1024, 768], 'thumbnail' => [320, 240]],
            $derivadas->map(fn (Arquivo $arquivo): array => [$arquivo->width, $arquivo->height])->all(),
        );

        foreach ($derivadas as $versao => $arquivo) {
            $conteudo = Storage::disk('local')->get($arquivo->storage_path);
            $imagem = $this->read($conteudo);

            $this->assertSame('image/webp', $arquivo->mime_type);
            $this->assertStringEndsWith("-{$versao}.webp", $arquivo->storage_path);
            $this->assertSame('WEBP', $imagem->getImageFormat());
            $this->assertSame([$arquivo->width, $arquivo->height], [$imagem->getImageWidth(), $imagem->getImageHeight()]);
            $this->assertSame(strlen($conteudo), $arquivo->file_size);
            $this->assertSame(hash('sha256', $conteudo), $arquivo->sha256);
        }

        $this->assertSame($originalBytes, Storage::disk('local')->get($original->storage_path));
    }

    public function test_small_originals_do_not_create_duplicate_versions(): void
    {
        [$item, $original] = $this->itemWithOriginal($this->photo(800, 600, 'jpeg'), 'image/jpeg', 800, 600);

        $result = $this->generate($item, $original);

        $this->assertSame(['large'], $result->dispensedVersions);
        $this->assertSame(['medium', 'thumbnail'], $this->derivatives($item)->keys()->all());
        $this->assertCount(2, Storage::disk('local')->files("acervo/derivados/{$item->id}"));
    }

    public function test_transparency_is_kept_in_webp_and_flattened_on_white_in_jpeg(): void
    {
        [$item, $original] = $this->itemWithOriginal($this->transparentPng(), 'image/png', 600, 400);

        $this->generate($item, $original);
        $webp = $this->read(Storage::disk('local')->get($this->derivatives($item)['thumbnail']->storage_path));

        $this->assertTrue($webp->getImageAlphaChannel());
        $this->assertEqualsWithDelta(0.0, $webp->getImagePixelColor(2, 2)->getColorValue(Imagick::COLOR_ALPHA), 0.05);
        $this->assertEqualsWithDelta(1.0, $webp->getImagePixelColor(160, 107)->getColorValue(Imagick::COLOR_ALPHA), 0.05);

        config(['acervo.optimized_output.format' => 'jpeg']);
        [$jpegItem, $jpegOriginal] = $this->itemWithOriginal($this->transparentPng(), 'image/png', 600, 400);
        $this->generate($jpegItem, $jpegOriginal);
        $miniatura = $this->derivatives($jpegItem)['thumbnail'];
        $jpeg = $this->read(Storage::disk('local')->get($miniatura->storage_path));

        $this->assertSame('image/jpeg', $miniatura->mime_type);
        $this->assertStringEndsWith('.jpg', $miniatura->storage_path);
        $canto = $jpeg->getImagePixelColor(2, 2)->getColor(true);
        $this->assertEqualsWithDelta(1.0, $canto['r'], 0.03);
        $this->assertEqualsWithDelta(1.0, $canto['g'], 0.03);
        $this->assertEqualsWithDelta(1.0, $canto['b'], 0.03);
    }

    public function test_exif_orientation_is_applied_and_metadata_is_removed(): void
    {
        // 400x200, vermelho à esquerda e azul à direita; a orientação 6 manda girar 90° no sentido horário.
        $jpeg = $this->withExifOrientation($this->twoColorJpeg(400, 200), 6);
        $this->assertSame(6, $this->read($jpeg)->getImageOrientation());
        [$item, $original] = $this->itemWithOriginal($jpeg, 'image/jpeg', 400, 200);

        $this->generate($item, $original);
        $derivadas = $this->derivatives($item);
        $media = $derivadas['medium'];
        $imagem = $this->read(Storage::disk('local')->get($media->storage_path));

        // De pé depois de girada: 200x400 na média e 160x320 na miniatura; a grande é dispensada.
        $this->assertSame(['medium', 'thumbnail'], $derivadas->keys()->all());
        $this->assertSame([160, 320], [$derivadas['thumbnail']->width, $derivadas['thumbnail']->height]);
        $this->assertSame([200, 400], [$media->width, $media->height]);
        $this->assertSame([200, 400], [$imagem->getImageWidth(), $imagem->getImageHeight()]);
        $this->assertGreaterThan(0.7, $imagem->getImagePixelColor(100, 20)->getColor(true)['r']);
        $this->assertGreaterThan(0.7, $imagem->getImagePixelColor(100, 380)->getColor(true)['b']);
        $this->assertSame([], $imagem->getImageProperties('exif:*'));
        $this->assertSame([], $imagem->getImageProfiles('*', false));
        $this->assertContains($imagem->getImageOrientation(), [Imagick::ORIENTATION_UNDEFINED, Imagick::ORIENTATION_TOPLEFT]);
    }

    public function test_cmyk_originals_are_converted_to_srgb(): void
    {
        $cmyk = new Imagick;
        $cmyk->newImage(600, 400, new ImagickPixel('rgb(30, 120, 200)'));
        $cmyk->transformImageColorspace(Imagick::COLORSPACE_CMYK);
        $cmyk->setImageFormat('jpeg');
        [$item, $original] = $this->itemWithOriginal($cmyk->getImageBlob(), 'image/jpeg', 600, 400);

        $this->generate($item, $original);
        $imagem = $this->read(Storage::disk('local')->get($this->derivatives($item)['thumbnail']->storage_path));
        $cor = $imagem->getImagePixelColor(100, 100)->getColor();

        $this->assertSame(Imagick::COLORSPACE_SRGB, $imagem->getImageColorspace());
        $this->assertEqualsWithDelta(200, $cor['b'], 25);
        $this->assertLessThan($cor['b'], $cor['r']);
    }

    public function test_embedded_profile_is_converted_with_the_server_srgb_profile(): void
    {
        $this->assertFileIsReadable(config('acervo.image_processing.srgb_profile'), 'O perfil sRGB deve estar instalado na imagem Docker.');
        [$item, $original] = $this->itemWithOriginal($this->withEmbeddedProfile(), 'image/jpeg', 600, 400);
        Log::spy();

        $render = app(OptimizedImageVersions::class)->render($item, $original, ['thumbnail'], app(GerenciarArquivoOriginal::class)->journal());

        $this->assertSame('profile', $render['metrics']['color']);
        $this->assertArrayHasKey('thumbnail', $render['files']);
        Log::shouldNotHaveReceived('warning');
    }

    public function test_missing_or_invalid_srgb_profile_falls_back_with_a_clear_log_and_keeps_the_original(): void
    {
        $invalido = tempnam(sys_get_temp_dir(), 'icc');
        file_put_contents($invalido, 'isto não é um perfil ICC');

        foreach (['/caminho/inexistente/sRGB.icc', $invalido] as $perfil) {
            config(['acervo.image_processing.srgb_profile' => $perfil]);
            [$item, $original] = $this->itemWithOriginal($this->withEmbeddedProfile(), 'image/jpeg', 600, 400);
            $antes = Storage::disk('local')->get($original->storage_path);
            Log::spy();

            $result = $this->generate($item, $original);

            $this->assertSame([], $result->failedVersions);
            $this->assertSame(['medium', 'thumbnail'], $this->derivatives($item)->keys()->all());
            $this->assertSame($antes, Storage::disk('local')->get($original->storage_path));
            Log::shouldHaveReceived('warning')->withArgs(
                fn (string $message, array $context): bool => $message === 'Perfil sRGB indisponível; as cores serão convertidas de forma aproximada.'
                    && $context['etapa'] === 'cor'
                    && $context['perfil'] === $perfil
                    && $context['arquivo_id'] === $original->id,
            )->once();
        }

        unlink($invalido);
    }

    public function test_processing_failures_are_logged_with_the_stage_and_never_reach_the_audit(): void
    {
        [$item, $original] = $this->itemWithOriginal('conteúdo corrompido', 'image/png', 800, 600);
        Log::spy();

        $this->generate($item, $original);

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $message === 'Arquivo original não pôde ser preparado para gerar versões otimizadas.'
                && $context['etapa'] === 'leitura'
                && $context['item_acervo_id'] === $item->id
                && $context['mime_type'] === 'image/png'
                && isset($context['erro']),
        )->once();
    }

    public function test_every_version_is_resized_from_the_same_decoded_original(): void
    {
        [$item, $original] = $this->itemWithOriginal($this->photo(2400, 1800, 'jpeg'), 'image/jpeg', 2400, 1800);

        $render = app(OptimizedImageVersions::class)->render($item, $original, ['large', 'thumbnail'], app(GerenciarArquivoOriginal::class)->journal());

        // Uma única leitura do original; cada versão tem seu tempo e sai do mesmo original.
        $this->assertGreaterThan(0, $render['metrics']['read_ms']);
        $this->assertEqualsCanonicalizing(['large', 'thumbnail'], array_keys($render['metrics']['versions_ms']));
        $this->assertSame([1920, 1440], [$render['files']['large']['width'], $render['files']['large']['height']]);
        $this->assertSame([320, 240], [$render['files']['thumbnail']['width'], $render['files']['thumbnail']['height']]);
    }

    public function test_multipage_and_16_bit_tiff_uses_the_first_page(): void
    {
        $tiff = new Imagick;

        foreach (['red', 'blue'] as $cor) {
            $pagina = new Imagick;
            $pagina->newImage(1200, 800, $cor);
            $pagina->setImageDepth(16);
            $pagina->setImageFormat('tiff');
            $tiff->addImage($pagina);
        }

        $tiff->setFormat('tiff');
        [$item, $original] = $this->itemWithOriginal($tiff->getImagesBlob(), 'image/tiff', 1200, 800);

        $result = $this->generate($item, $original);
        $imagem = $this->read(Storage::disk('local')->get($this->derivatives($item)['medium']->storage_path));

        $this->assertSame([], $result->failedVersions);
        $this->assertSame([1024, 683], [$imagem->getImageWidth(), $imagem->getImageHeight()]);
        $this->assertGreaterThan(0.9, $imagem->getImagePixelColor(10, 10)->getColor(true)['r']);
    }

    public function test_quality_is_configurable(): void
    {
        $foto = $this->photo(1200, 900, 'png');

        config(['acervo.optimized_output.quality' => 95]);
        [$alta, $originalAlta] = $this->itemWithOriginal($foto, 'image/png', 1200, 900);
        $this->generate($alta, $originalAlta);

        config(['acervo.optimized_output.quality' => 30]);
        [$baixa, $originalBaixa] = $this->itemWithOriginal($foto, 'image/png', 1200, 900);
        $this->generate($baixa, $originalBaixa);

        $this->assertLessThan(
            $this->derivatives($alta)['medium']->file_size,
            $this->derivatives($baixa)['medium']->file_size,
        );
    }

    public function test_gd_engine_still_produces_webp_but_cannot_read_tiff(): void
    {
        config(['acervo.image_processing.engine' => 'gd']);
        [$item, $original] = $this->itemWithOriginal($this->photo(1600, 1200, 'png'), 'image/png', 1600, 1200);

        $this->assertSame([], $this->generate($item, $original)->failedVersions);
        $this->assertSame(['image/webp'], $this->derivatives($item)->pluck('mime_type')->unique()->values()->all());
        $this->assertSame([1024, 768], [$this->derivatives($item)['medium']->width, $this->derivatives($item)['medium']->height]);

        Log::spy();
        [$tiffItem, $tiffOriginal] = $this->itemWithOriginal($this->photo(800, 600, 'tiff'), 'image/tiff', 800, 600);

        $this->assertSame(['thumbnail', 'medium', 'large'], $this->generate($tiffItem, $tiffOriginal)->failedVersions);
        $this->assertSame(0, $this->derivatives($tiffItem)->count());
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => str_contains($context['erro'] ?? '', 'Imagick'),
        )->once();
    }

    public function test_unreadable_original_or_invalid_output_fails_every_version_without_leftovers(): void
    {
        [$item, $original] = $this->itemWithOriginal('isto não é uma imagem', 'image/jpeg', 800, 600);

        $this->assertSame(['thumbnail', 'medium', 'large'], $this->generate($item, $original)->failedVersions);
        $this->assertSame([], Storage::disk('local')->files("acervo/derivados/{$item->id}"));

        config(['acervo.optimized_output.format' => 'gif']);
        [$outro, $outroOriginal] = $this->itemWithOriginal($this->photo(800, 600, 'jpeg'), 'image/jpeg', 800, 600);

        $this->assertSame(['thumbnail', 'medium', 'large'], $this->generate($outro, $outroOriginal)->failedVersions);
        $this->assertSame(0, $this->derivatives($outro)->count());
    }

    public function test_full_regeneration_migrates_jpeg_versions_to_webp_and_removes_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
        [$item, $original] = $this->itemWithOriginal($this->photo(800, 600, 'jpeg'), 'image/jpeg', 800, 600);
        $originalBytes = Storage::disk('local')->get($original->storage_path);
        // Acervo antigo: três versões em JPEG, com a grande repetindo a média.
        $antigas = collect(['thumbnail' => [320, 240], 'medium' => [800, 600], 'large' => [800, 600]])
            ->map(fn (array $dimensoes, string $versao) => $this->legacyJpeg($item, $versao, ...$dimensoes));

        $result = app(RegenerarVersoesOtimizadas::class)->execute(
            $admin,
            $item,
            AuditContext::forUser($admin, AuditSource::Web, (string) Str::uuid()),
        );

        $derivadas = $this->derivatives($item);
        $this->assertSame(['large'], $result->dispensedVersions);
        $this->assertSame(['medium', 'thumbnail'], $derivadas->keys()->all());
        $this->assertSame(['image/webp'], $derivadas->pluck('mime_type')->unique()->values()->all());

        foreach ($antigas as $antiga) {
            $this->assertNull(Arquivo::find($antiga->id));
            Storage::disk('local')->assertMissing($antiga->storage_path);
        }

        $this->assertSame($derivadas->pluck('storage_path')->sort()->values()->all(), collect(Storage::disk('local')->files("acervo/derivados/{$item->id}"))->sort()->values()->all());
        $this->assertSame($originalBytes, Storage::disk('local')->get($original->storage_path));
        $this->assertSame($original->only(['id', 'storage_path', 'sha256', 'mime_type']), $original->fresh()->only(['id', 'storage_path', 'sha256', 'mime_type']));

        $evento = AuditEvent::query()->where('action', AuditAction::Regenerated)->sole();
        $this->assertSame(['large'], $evento->metadata['derivations']['dispensed_versions']);
        $this->assertCount(3, $evento->metadata['derivations_removed']);
    }

    public function test_missing_mode_ignores_dispensed_versions(): void
    {
        [$item, $original] = $this->itemWithOriginal($this->photo(800, 600, 'jpeg'), 'image/jpeg', 800, 600);
        $this->generate($item, $original);

        $this->artisan('acervo:regenerar-versoes', ['--ausentes' => true])
            ->expectsOutputToContain('0 fotografia(s) processada(s)')
            ->assertSuccessful();

        $this->assertSame(0, AuditEvent::query()->where('action', AuditAction::Regenerated)->count());
    }

    public function test_public_pages_fall_back_to_existing_versions_and_srcset_has_unique_widths(): void
    {
        $item = ItemAcervo::create($this->itemAttributes(['status' => 'publicado', 'visibilidade' => Visibilidade::Publico]));
        $this->storeOriginal($item, $this->photo(800, 600, 'jpeg'), 'image/jpeg', 800, 600);
        $miniatura = $this->legacyJpeg($item, 'thumbnail', 320, 240);
        $media = $this->legacyJpeg($item, 'medium', 800, 600);
        // Registro antigo com a mesma largura da média.
        $this->legacyJpeg($item, 'large', 800, 600);

        $html = $this->get(route('public.catalogo'))->assertOk()->getContent();
        preg_match('/srcset="([^"]+)"/', $html, $srcset);
        $larguras = collect(explode(',', $srcset[1]))->map(fn (string $item): string => Str::afterLast(trim($item), ' '));

        $this->assertSame(['320w', '800w'], $larguras->all());

        Arquivo::query()->where('item_acervo_id', $item->id)->where('versao_arquivo', 'large')->delete();

        $this->get(route('public.fotografias.show', $item->id))->assertOk()
            ->assertSee(route('public.imagens.show', $media), false);
        $this->get(route('public.imagens.show', $media))->assertOk();
        $this->get(route('public.catalogo'))->assertOk()
            ->assertSee(route('public.imagens.show', $miniatura).' 320w', false);
    }

    private function generate(ItemAcervo $item, Arquivo $original): OptimizedImageResult
    {
        return app(OptimizedImageVersions::class)->generate($item, $original, app(GerenciarArquivoOriginal::class)->journal());
    }

    /**
     * @return array{0: ItemAcervo, 1: Arquivo}
     */
    private function itemWithOriginal(string $conteudo, string $mime, int $largura, int $altura): array
    {
        $item = ItemAcervo::create($this->itemAttributes());

        return [$item, $this->storeOriginal($item, $conteudo, $mime, $largura, $altura)];
    }

    private function storeOriginal(ItemAcervo $item, string $conteudo, string $mime, int $largura, int $altura): Arquivo
    {
        $caminho = "acervo/originais/{$item->id}/".Str::uuid().'.'.Str::after($mime, '/');
        Storage::disk('local')->put($caminho, $conteudo);

        return $item->arquivos()->create([
            'nome_original' => 'original.'.Str::after($mime, '/'),
            'provider' => 'local',
            'storage_path' => $caminho,
            'mime_type' => $mime,
            'file_size' => strlen($conteudo),
            'tipo_arquivo' => 'imagem',
            'sha256' => hash('sha256', $conteudo),
            'versao_arquivo' => 'original',
            'width' => $largura,
            'height' => $altura,
        ]);
    }

    private function legacyJpeg(ItemAcervo $item, string $versao, int $largura, int $altura): Arquivo
    {
        $conteudo = $this->photo($largura, $altura, 'jpeg');
        $caminho = "acervo/derivados/{$item->id}/".Str::uuid()."-{$versao}.jpg";
        Storage::disk('local')->put($caminho, $conteudo);

        return $item->arquivos()->create([
            'provider' => 'local',
            'storage_path' => $caminho,
            'mime_type' => 'image/jpeg',
            'file_size' => strlen($conteudo),
            'tipo_arquivo' => 'imagem',
            'sha256' => hash('sha256', $conteudo),
            'versao_arquivo' => $versao,
            'width' => $largura,
            'height' => $altura,
        ]);
    }

    /** @return Collection<string, Arquivo> */
    private function derivatives(ItemAcervo $item)
    {
        return $item->arquivos()
            ->where('versao_arquivo', '!=', 'original')
            ->orderBy('versao_arquivo')
            ->get()
            ->keyBy('versao_arquivo');
    }

    /** @return array<string, mixed> */
    private function itemAttributes(array $override = []): array
    {
        return $override + [
            'titulo' => 'Praça central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ];
    }

    /**
     * Imagem com textura (não uniforme), para que a qualidade faça diferença no tamanho.
     */
    private function photo(int $largura, int $altura, string $formato): string
    {
        $imagem = new Imagick;
        $imagem->newPseudoImage($largura, $altura, 'plasma:fractal');
        $imagem->setImageFormat($formato);

        return $imagem->getImageBlob();
    }

    private function transparentPng(): string
    {
        $imagem = new Imagick;
        $imagem->newImage(600, 400, new ImagickPixel('transparent'));
        $quadrado = new ImagickDraw;
        $quadrado->setFillColor(new ImagickPixel('rgb(20, 90, 160)'));
        $quadrado->rectangle(150, 100, 450, 300);
        $imagem->drawImage($quadrado);
        $imagem->setImageFormat('png');

        return $imagem->getImageBlob();
    }

    private function twoColorJpeg(int $largura, int $altura): string
    {
        $imagem = new Imagick;
        $imagem->newImage($largura, $altura, 'blue');
        $metade = new ImagickDraw;
        $metade->setFillColor('red');
        $metade->rectangle(0, 0, intdiv($largura, 2) - 1, $altura - 1);
        $imagem->drawImage($metade);
        $imagem->setImageFormat('jpeg');

        return $imagem->getImageBlob();
    }

    /**
     * Insere um bloco EXIF mínimo (APP1) só com a tag Orientation, como faz uma câmera.
     */
    private function withExifOrientation(string $jpeg, int $orientacao): string
    {
        $tiff = "MM\x00\x2A\x00\x00\x00\x08"."\x00\x01"."\x01\x12\x00\x03\x00\x00\x00\x01".pack('n', $orientacao)."\x00\x00"."\x00\x00\x00\x00";
        $app1 = "Exif\x00\x00".$tiff;
        $segmento = "\xFF\xE1".pack('n', strlen($app1) + 2).$app1;
        // Mantém o APP0 (JFIF) logo após o início do arquivo, se existir.
        $posicao = substr($jpeg, 2, 2) === "\xFF\xE0" ? 4 + unpack('n', substr($jpeg, 4, 2))[1] : 2;

        return substr($jpeg, 0, $posicao).$segmento.substr($jpeg, $posicao);
    }

    /**
     * JPEG com perfil ICC embutido (o próprio sRGB do servidor, ou o do Ghostscript).
     */
    private function withEmbeddedProfile(): string
    {
        $perfil = collect([config('acervo.image_processing.srgb_profile'), '/usr/share/color/icc/sRGB.icc', '/usr/share/color/icc/ghostscript/srgb.icc'])
            ->first(fn (?string $caminho): bool => is_string($caminho) && is_readable($caminho));

        if ($perfil === null) {
            $this->markTestSkipped('Nenhum perfil ICC disponível para embutir no teste.');
        }

        $imagem = $this->read($this->photo(600, 400, 'jpeg'));
        $imagem->setImageProfile('icc', (string) file_get_contents($perfil));
        $imagem->setImageFormat('jpeg');

        return $imagem->getImageBlob();
    }

    private function read(string $conteudo): Imagick
    {
        $imagem = new Imagick;
        $imagem->readImageBlob($conteudo);

        return $imagem;
    }
}
