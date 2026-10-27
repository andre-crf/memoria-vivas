<?php

namespace App\Console\Commands;

use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Services\Arquivos\Contracts\ArquivoStorage;
use App\Services\Arquivos\StorageMutationJournal;
use App\Support\OptimizedImageVersions;
use finfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Imagick;
use JsonException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Mede o processamento de imagens reais sem tocar no acervo.
 *
 * Cada arquivo é copiado para uma pasta temporária e processado pelo mesmo código
 * da geração das versões, sem banco de dados, sem auditoria e sem alterar o arquivo
 * de entrada. Cada imagem roda num processo próprio: o pico de memória do processo
 * (VmHWM) inclui o ImageMagick, que fica fora do memory_limit do PHP.
 */
class MedirProcessamentoImagens extends Command
{
    private const DISK = 'medicao_processamento';

    protected $signature = 'acervo:medir-processamento
        {caminhos* : Arquivos de imagem ou pastas com imagens}
        {--formato= : Formato das versões (webp ou jpeg); padrão: config/acervo.php}
        {--qualidade= : Qualidade das versões (1 a 100); padrão: config/acervo.php}
        {--motor= : Motor de processamento (auto, imagick ou gd); padrão: config/acervo.php}
        {--saida= : Pasta onde guardar as versões geradas para inspeção visual}
        {--csv= : Grava também os resultados num arquivo CSV}
        {--tempo-limite=600 : Segundos por imagem antes de desistir}
        {--interno : Uso interno: mede um único arquivo neste processo e imprime JSON}';

    protected $description = 'Mede tempo, dimensões, tamanhos e memória do processamento de imagens, sem alterar o acervo';

    public function handle(): int
    {
        return $this->option('interno') ? $this->measureHere() : $this->measureEach();
    }

    private function measureEach(): int
    {
        $arquivos = $this->files((array) $this->argument('caminhos'));

        if ($arquivos === []) {
            $this->error('Nenhuma imagem encontrada nos caminhos informados.');

            return self::INVALID;
        }

        $resultados = [];

        foreach ($arquivos as $arquivo) {
            $resultado = $this->runIsolated($arquivo);
            $resultados[] = $resultado;
            $this->report($resultado);
        }

        if ($csv = $this->option('csv')) {
            $this->writeCsv((string) $csv, $resultados);
            $this->info("Resultados gravados em {$csv}.");
        }

        $falhas = collect($resultados)->filter(fn (array $resultado): bool => isset($resultado['erro']) || $resultado['falhas'] !== [])->count();
        $this->newLine();
        $this->line(sprintf('%d imagem(ns) medida(s), %d com falha.', count($resultados), $falhas));

        return $falhas > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function runIsolated(string $arquivo): array
    {
        $comando = [PHP_BINARY, base_path('artisan'), 'acervo:medir-processamento', $arquivo, '--interno'];

        foreach (['formato', 'qualidade', 'motor', 'saida'] as $opcao) {
            if (filled($this->option($opcao))) {
                $comando[] = "--{$opcao}=".$this->option($opcao);
            }
        }

        $processo = new Process($comando, base_path(), timeout: (float) $this->option('tempo-limite'));

        try {
            $processo->run();
        } catch (Throwable $exception) {
            return ['arquivo' => $arquivo, 'erro' => 'Processo interrompido: '.$exception->getMessage()];
        }

        $linhas = array_filter(explode("\n", trim($processo->getOutput())));

        try {
            $resultado = json_decode((string) end($linhas), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $resultado = null;
        }

        if (! is_array($resultado)) {
            // Erro fatal (por exemplo, falta de memória): o processo morre sem imprimir o JSON.
            $saida = trim($processo->getErrorOutput()."\n".$processo->getOutput());

            return ['arquivo' => $arquivo, 'erro' => "O processo terminou com código {$processo->getExitCode()}: ".Str::limit($saida, 300)];
        }

        return $resultado;
    }

    private function measureHere(): int
    {
        $arquivo = (string) ((array) $this->argument('caminhos'))[0];
        $mime = is_file($arquivo) ? (string) (new finfo(FILEINFO_MIME_TYPE))->file($arquivo) : '';

        if (! str_starts_with($mime, 'image/') || ! in_array($mime, config('acervo.uploads.original.mime_types', []), true)) {
            $this->line(json_encode(['arquivo' => $arquivo, 'erro' => 'Arquivo inexistente ou formato não aceito pelo acervo.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::FAILURE;
        }

        foreach (['formato' => 'acervo.optimized_output.format', 'qualidade' => 'acervo.optimized_output.quality', 'motor' => 'acervo.image_processing.engine'] as $opcao => $chave) {
            if (filled($this->option($opcao))) {
                config([$chave => $opcao === 'qualidade' ? (int) $this->option($opcao) : $this->option($opcao)]);
            }
        }

        $pasta = storage_path('framework/medicao-'.Str::uuid());
        File::ensureDirectoryExists($pasta);
        // Só neste processo: o provider "local" passa a apontar para a pasta temporária,
        // então nada é lido ou gravado no armazenamento do acervo.
        config([
            'filesystems.disks.'.self::DISK => ['driver' => 'local', 'root' => $pasta, 'throw' => false],
            'acervo.storage_disks.local' => self::DISK,
        ]);

        try {
            // O arquivo de entrada só é lido. Um link evita copiar o arquivo pela memória do
            // processo, o que inflaria o pico medido; sem suporte a links, faz uma cópia.
            $extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION)) ?: 'img';
            $entrada = "{$pasta}/original.{$extensao}";

            if (! @symlink((string) realpath($arquivo), $entrada)) {
                File::copy($arquivo, $entrada);
            }
            [$largura, $altura] = @getimagesize($arquivo) ?: [null, null];
            $original = new Arquivo([
                'provider' => 'local',
                'storage_path' => "original.{$extensao}",
                'mime_type' => $mime,
                'file_size' => filesize($arquivo),
                'tipo_arquivo' => 'imagem',
                'versao_arquivo' => 'original',
                'width' => $largura,
                'height' => $altura,
            ]);
            $item = (new ItemAcervo(['titulo' => basename($arquivo)]))->setAttribute('id', 0);
            $versoes = app(OptimizedImageVersions::class);

            $rssAntes = $this->processMemory();
            memory_reset_peak_usage();
            $inicio = hrtime(true);
            $render = $versoes->render($item, $original, $versoes->versions(), new StorageMutationJournal(app(ArquivoStorage::class)));
            $totalMs = round((hrtime(true) - $inicio) / 1_000_000, 1);
            $picoPhp = memory_get_peak_usage(true);
            $rssDepois = $this->processMemory();

            $geradas = collect($render['files'])->map(function (array $atributos, string $versao) use ($pasta, $arquivo, $render): array {
                $this->keepForInspection("{$pasta}/{$atributos['storage_path']}", $arquivo, $versao);

                return [
                    'largura' => $atributos['width'],
                    'altura' => $atributos['height'],
                    'bytes' => $atributos['file_size'],
                    'mime_type' => $atributos['mime_type'],
                    'ms' => $render['metrics']['versions_ms'][$versao] ?? null,
                ];
            })->all();

            $this->line(json_encode([
                'arquivo' => $arquivo,
                'mime_type' => $mime,
                'largura' => $largura,
                'altura' => $altura,
                'bytes' => filesize($arquivo),
                'motor' => $render['metrics']['engine'],
                'cor' => $render['metrics']['color'],
                'formato_saida' => config('acervo.optimized_output.format'),
                'qualidade' => config('acervo.optimized_output.quality'),
                'leitura_ms' => $render['metrics']['read_ms'],
                'total_ms' => $totalMs,
                'versoes' => $geradas,
                'dispensadas' => $render['dispensed'],
                'falhas' => $render['failed'],
                'memoria' => [
                    'php_pico_bytes' => $picoPhp,
                    'php_limite' => ini_get('memory_limit'),
                    'processo_pico_antes_bytes' => $rssAntes['pico'],
                    'processo_pico_bytes' => $rssDepois['pico'],
                    'imagick_limites' => $this->imagickLimits(),
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $render['failed'] === [] ? self::SUCCESS : self::FAILURE;
        } finally {
            File::deleteDirectory($pasta);
        }
    }

    private function keepForInspection(string $gerado, string $arquivo, string $versao): void
    {
        $saida = $this->option('saida');

        if (! filled($saida) || ! is_file($gerado)) {
            return;
        }

        File::ensureDirectoryExists((string) $saida);
        File::copy($gerado, rtrim((string) $saida, '/').'/'.pathinfo($arquivo, PATHINFO_FILENAME)."-{$versao}.".pathinfo($gerado, PATHINFO_EXTENSION));
    }

    /**
     * Memória do processo inteiro (PHP + ImageMagick), lida do Linux. VmHWM é o pico.
     *
     * @return array{pico: int|null, atual: int|null}
     */
    private function processMemory(): array
    {
        $status = @file_get_contents('/proc/self/status') ?: '';
        $kb = fn (string $campo): ?int => preg_match("/^{$campo}:\\s+(\\d+) kB/m", $status, $m) ? (int) $m[1] * 1024 : null;

        return ['pico' => $kb('VmHWM'), 'atual' => $kb('VmRSS')];
    }

    /**
     * @return array<string, int>|null
     */
    private function imagickLimits(): ?array
    {
        if (! extension_loaded('imagick')) {
            return null;
        }

        return [
            'memoria_bytes' => Imagick::getResourceLimit(Imagick::RESOURCETYPE_MEMORY),
            'mapa_bytes' => Imagick::getResourceLimit(Imagick::RESOURCETYPE_MAP),
            'area_pixels' => Imagick::getResourceLimit(Imagick::RESOURCETYPE_AREA),
            'disco_bytes' => Imagick::getResourceLimit(Imagick::RESOURCETYPE_DISK),
        ];
    }

    /**
     * @param  list<string>  $caminhos
     * @return list<string>
     */
    private function files(array $caminhos): array
    {
        $extensoes = array_diff(config('acervo.uploads.original.extensions', []), ['pdf']);

        return collect($caminhos)
            ->flatMap(fn (string $caminho): array => is_dir($caminho)
                ? collect(File::files($caminho))->map(fn ($arquivo): string => $arquivo->getPathname())->all()
                : [$caminho])
            ->filter(fn (string $caminho): bool => ! is_dir($caminho) && (! is_file($caminho) || in_array(strtolower(pathinfo($caminho, PATHINFO_EXTENSION)), $extensoes, true)))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $resultado
     */
    private function report(array $resultado): void
    {
        $this->newLine();
        $this->line('<options=bold>'.basename((string) $resultado['arquivo']).'</>');

        if (isset($resultado['erro'])) {
            $this->error('  '.$resultado['erro']);

            return;
        }

        $this->line(sprintf(
            '  Original: %s, %s × %s px, %s · motor %s, cores: %s · saída %s q%s',
            $resultado['mime_type'],
            $resultado['largura'] ?? '?',
            $resultado['altura'] ?? '?',
            $this->size($resultado['bytes']),
            $resultado['motor'] ?? '?',
            $resultado['cor'] ?? '—',
            $resultado['formato_saida'],
            $resultado['qualidade'],
        ));
        $this->table(
            ['Versão', 'Dimensões', 'Tamanho', 'Tempo'],
            collect($resultado['versoes'])->map(fn (array $versao, string $nome): array => [
                $nome,
                "{$versao['largura']} × {$versao['altura']}",
                $this->size($versao['bytes']),
                "{$versao['ms']} ms",
            ])->values()->all(),
        );

        $memoria = $resultado['memoria'];
        $this->line(sprintf(
            '  Leitura %s ms · total %s ms · pico PHP %s (limite %s) · pico do processo %s (antes %s)',
            $resultado['leitura_ms'],
            $resultado['total_ms'],
            $this->size($memoria['php_pico_bytes']),
            $memoria['php_limite'],
            $this->size($memoria['processo_pico_bytes']),
            $this->size($memoria['processo_pico_antes_bytes']),
        ));

        if ($resultado['dispensadas'] !== []) {
            $this->line('  Dispensadas: '.implode(', ', $resultado['dispensadas']));
        }

        if ($resultado['falhas'] !== []) {
            $this->warn('  Falhas: '.implode(', ', $resultado['falhas']).' (detalhes no log)');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $resultados
     */
    private function writeCsv(string $caminho, array $resultados): void
    {
        $handle = fopen($caminho, 'w');
        fputcsv($handle, ['arquivo', 'mime_type', 'largura', 'altura', 'bytes', 'motor', 'cor', 'formato_saida', 'qualidade', 'versao', 'versao_largura', 'versao_altura', 'versao_bytes', 'versao_ms', 'leitura_ms', 'total_ms', 'php_pico_bytes', 'processo_pico_bytes', 'processo_pico_antes_bytes', 'dispensadas', 'falhas', 'erro'], escape: '');

        foreach ($resultados as $resultado) {
            $versoes = ($resultado['versoes'] ?? []) ?: [null => null];

            foreach ($versoes as $nome => $versao) {
                fputcsv($handle, [
                    $resultado['arquivo'], $resultado['mime_type'] ?? null, $resultado['largura'] ?? null, $resultado['altura'] ?? null,
                    $resultado['bytes'] ?? null, $resultado['motor'] ?? null, $resultado['cor'] ?? null, $resultado['formato_saida'] ?? null,
                    $resultado['qualidade'] ?? null, $nome ?: null, $versao['largura'] ?? null, $versao['altura'] ?? null, $versao['bytes'] ?? null,
                    $versao['ms'] ?? null, $resultado['leitura_ms'] ?? null, $resultado['total_ms'] ?? null,
                    $resultado['memoria']['php_pico_bytes'] ?? null, $resultado['memoria']['processo_pico_bytes'] ?? null,
                    $resultado['memoria']['processo_pico_antes_bytes'] ?? null,
                    implode(' ', $resultado['dispensadas'] ?? []), implode(' ', $resultado['falhas'] ?? []), $resultado['erro'] ?? null,
                ], escape: '');
            }
        }

        fclose($handle);
    }

    private function size(?int $bytes): string
    {
        return match (true) {
            $bytes === null => '?',
            $bytes >= 1024 * 1024 => number_format($bytes / 1024 / 1024, 1, ',', '.').' MB',
            default => number_format($bytes / 1024, 1, ',', '.').' KB',
        };
    }
}
