<?php

namespace App\Services\Arquivos;

use App\Services\Arquivos\Contracts\ArquivoStorage;
use Illuminate\Support\Facades\Log;
use Throwable;

final class StorageMutationJournal
{
    /** @var array<string, list<string>> */
    private array $created = [];

    /** @var array<string, list<string>> */
    private array $obsolete = [];

    public function __construct(
        private readonly ArquivoStorage $storage,
    ) {}

    public function created(string $provider, string $path): void
    {
        $this->created[$provider][] = $path;
    }

    public function obsolete(string $provider, string $path): void
    {
        $this->obsolete[$provider][] = $path;
    }

    public function rollbackCreated(): void
    {
        $this->cleanup($this->created, 'rollback');
    }

    public function cleanupObsolete(): void
    {
        $this->cleanup($this->obsolete, 'post_commit');
    }

    public function cleanupFailedCreation(string $provider, string $path): void
    {
        $this->cleanup([$provider => [$path]], 'derivation_failure');
    }

    /**
     * @param  array<string, list<string>>  $entries
     */
    private function cleanup(array $entries, string $phase): void
    {
        foreach ($entries as $provider => $paths) {
            $paths = array_values(array_unique(array_filter($paths)));

            try {
                $deleted = $this->storage->delete($provider, $paths);

                if (! $deleted) {
                    Log::warning('A limpeza de arquivos do acervo não foi concluída.', [
                        'provider' => $provider,
                        'paths' => $paths,
                        'phase' => $phase,
                    ]);
                }
            } catch (Throwable $exception) {
                Log::warning('Falha ao limpar arquivos do acervo.', [
                    'provider' => $provider,
                    'paths' => $paths,
                    'phase' => $phase,
                    'erro' => $exception->getMessage(),
                    'exception' => $exception,
                ]);
            }
        }
    }
}
