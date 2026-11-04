<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use Tests\TestCase;

class MedirProcessamentoImagensTest extends TestCase
{
    use RefreshDatabase;

    private string $pasta;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Execute no Docker, com Imagick, para medir o processamento.');
        }

        Storage::fake('local');
        $this->pasta = sys_get_temp_dir().'/medicao-teste-'.Str::uuid();
        File::ensureDirectoryExists($this->pasta);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pasta);

        parent::tearDown();
    }

    public function test_internal_measurement_reports_versions_time_and_memory_without_touching_the_acervo(): void
    {
        $foto = $this->image('praca.jpg', 2400, 1600, 'jpeg');
        $hash = hash_file('sha256', $foto);

        Artisan::call('acervo:medir-processamento', ['caminhos' => [$foto], '--interno' => true]);
        $resultado = json_decode(trim(Artisan::output()), true);

        $this->assertSame('image/jpeg', $resultado['mime_type']);
        $this->assertSame([2400, 1600], [$resultado['largura'], $resultado['altura']]);
        $this->assertSame('imagick', $resultado['motor']);
        $this->assertSame('webp', $resultado['formato_saida']);
        $this->assertSame(['thumbnail', 'medium', 'large'], array_keys($resultado['versoes']));
        $this->assertSame([1920, 1280], [$resultado['versoes']['large']['largura'], $resultado['versoes']['large']['altura']]);
        $this->assertSame('image/webp', $resultado['versoes']['large']['mime_type']);
        $this->assertGreaterThan(0, $resultado['versoes']['thumbnail']['bytes']);
        $this->assertGreaterThan(0, $resultado['total_ms']);
        $this->assertGreaterThan(0, $resultado['memoria']['php_pico_bytes']);
        $this->assertGreaterThanOrEqual($resultado['memoria']['processo_pico_antes_bytes'], $resultado['memoria']['processo_pico_bytes']);
        $this->assertArrayHasKey('memoria_bytes', $resultado['memoria']['imagick_limites']);

        $this->assertSame($hash, hash_file('sha256', $foto));
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('arquivos', 0);
        $this->assertDatabaseCount('item_acervos', 0);
        $this->assertDatabaseCount('audit_events', 0);
        $this->assertSame([], glob(storage_path('framework/medicao-*')));
    }

    public function test_options_override_format_and_quality_and_outputs_can_be_kept(): void
    {
        $foto = $this->image('retrato.png', 800, 600, 'png');
        $saida = "{$this->pasta}/saida";

        Artisan::call('acervo:medir-processamento', [
            'caminhos' => [$foto], '--interno' => true, '--formato' => 'jpeg', '--qualidade' => 60, '--saida' => $saida,
        ]);
        $resultado = json_decode(trim(Artisan::output()), true);

        $this->assertSame(['jpeg', 60], [$resultado['formato_saida'], $resultado['qualidade']]);
        $this->assertSame(['large'], $resultado['dispensadas']);
        $this->assertSame(['retrato-medium.jpg', 'retrato-thumbnail.jpg'], collect(File::files($saida))->map->getFilename()->sort()->values()->all());
    }

    public function test_internal_measurement_rejects_files_outside_the_accepted_formats(): void
    {
        $texto = "{$this->pasta}/notas.txt";
        file_put_contents($texto, 'não é imagem');

        $this->assertSame(1, Artisan::call('acervo:medir-processamento', ['caminhos' => [$texto], '--interno' => true]));
        $this->assertStringContainsString('formato não aceito', Artisan::output());
    }

    public function test_each_image_is_measured_in_its_own_process_and_written_to_csv(): void
    {
        $this->image('a.jpg', 1200, 900, 'jpeg');
        $this->image('b.tiff', 600, 400, 'tiff');
        file_put_contents("{$this->pasta}/ignorado.txt", 'texto');
        $csv = "{$this->pasta}/resultados.csv";

        $this->artisan('acervo:medir-processamento', ['caminhos' => [$this->pasta], '--csv' => $csv])
            ->expectsOutputToContain('2 imagem(ns) medida(s), 0 com falha.')
            ->assertSuccessful();

        $linhas = array_map('str_getcsv', file($csv, FILE_IGNORE_NEW_LINES));
        $cabecalho = array_shift($linhas);
        $registros = array_map(fn (array $linha): array => array_combine($cabecalho, $linha), $linhas);

        // a.jpg: miniatura, média e grande; b.tiff (600 px): miniatura e média.
        $this->assertCount(5, $registros);
        $this->assertSame(['a.jpg', 'b.tiff'], array_values(array_unique(array_map(fn (array $registro): string => basename($registro['arquivo']), $registros))));
        $this->assertNotSame('', $registros[0]['processo_pico_bytes']);
    }

    private function image(string $nome, int $largura, int $altura, string $formato): string
    {
        $imagem = new Imagick;
        $imagem->newPseudoImage($largura, $altura, 'plasma:fractal');
        $imagem->setImageFormat($formato);
        $caminho = "{$this->pasta}/{$nome}";
        $imagem->writeImage($caminho);

        return $caminho;
    }
}
