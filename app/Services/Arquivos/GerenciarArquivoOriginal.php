<?php

namespace App\Services\Arquivos;

use App\Auditing\ArquivoAuditSnapshot;
use App\Auditing\AuditEventCollector;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Services\Arquivos\Contracts\ArquivoStorage;
use App\Support\OptimizedImageResult;
use App\Support\OptimizedImageVersions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class GerenciarArquivoOriginal
{
    private const PROVIDER = 'local';

    public function __construct(
        private ArquivoStorage $storage,
        private OptimizedImageVersions $optimizedVersions,
    ) {}

    public function journal(): StorageMutationJournal
    {
        return new StorageMutationJournal($this->storage);
    }

    public function upload(
        User $actor,
        ItemAcervo $item,
        UploadedFile $file,
        AuditEventCollector $audit,
        StorageMutationJournal $journal,
    ): Arquivo {
        Gate::forUser($actor)->authorize('uploadOriginal', Arquivo::class);

        if ($item->arquivos()->where('versao_arquivo', 'original')->exists()) {
            throw ValidationException::withMessages([
                'arquivo_original' => 'Esta fotografia já possui arquivo original vinculado.',
            ]);
        }

        $sha256 = $this->hash($file);
        $this->ensureNotDuplicated($sha256, $item);
        $original = $item->arquivos()->create(
            $this->storeOriginalAttributes($item, $file, $sha256, $journal),
        );
        $derivations = $this->optimizedVersions->generate($item, $original, $journal);

        $audit->capture(
            action: AuditAction::Uploaded,
            subjectType: AuditEntity::Arquivo,
            subjectId: $original->id,
            after: ArquivoAuditSnapshot::capture($original->refresh()),
            subjectLabel: $original->nome_original,
            metadata: $this->metadata(
                operation: 'arquivo_original_upload',
                item: $item,
                derivations: $derivations,
            ),
        );

        return $original;
    }

    public function replace(
        User $actor,
        ItemAcervo $item,
        UploadedFile $file,
        AuditEventCollector $audit,
        StorageMutationJournal $journal,
    ): Arquivo {
        $original = $item->arquivos()
            ->where('versao_arquivo', 'original')
            ->lockForUpdate()
            ->firstOrFail();
        Gate::forUser($actor)->authorize('replaceOriginal', $original);

        $sha256 = $this->hash($file);
        $this->ensureNotDuplicated($sha256, $item);

        $before = ArquivoAuditSnapshot::capture($original);
        $removedDerivations = $item->arquivos()
            ->where('versao_arquivo', '!=', 'original')
            ->orderBy('id')
            ->get();

        foreach ([...$removedDerivations, $original] as $obsolete) {
            if ($obsolete->storage_path) {
                $journal->obsolete($obsolete->provider, $obsolete->storage_path);
            }
        }

        $removedDerivations->each->delete();
        $original->update($this->storeOriginalAttributes($item, $file, $sha256, $journal));
        $original->refresh();
        $derivations = $this->optimizedVersions->generate($item, $original, $journal);

        $audit->capture(
            action: AuditAction::Replaced,
            subjectType: AuditEntity::Arquivo,
            subjectId: $original->id,
            before: $before,
            after: ArquivoAuditSnapshot::capture($original),
            subjectLabel: $original->nome_original,
            metadata: [
                ...$this->metadata(
                    operation: 'arquivo_original_replace',
                    item: $item,
                    derivations: $derivations,
                ),
                'derivations_removed' => ArquivoAuditSnapshot::summaries($removedDerivations),
            ],
        );

        return $original;
    }

    public function captureDeletion(
        ItemAcervo $item,
        AuditEventCollector $audit,
        StorageMutationJournal $journal,
    ): void {
        $arquivos = $item->arquivos()->orderBy('id')->lockForUpdate()->get();
        $original = $arquivos->first(fn (Arquivo $arquivo): bool => $arquivo->isOriginal());

        foreach ($arquivos as $arquivo) {
            if ($arquivo->storage_path) {
                $journal->obsolete($arquivo->provider, $arquivo->storage_path);
            }
        }

        if ($original instanceof Arquivo) {
            $derivations = $arquivos->reject(fn (Arquivo $arquivo): bool => $arquivo->isOriginal());

            $audit->capture(
                action: AuditAction::Deleted,
                subjectType: AuditEntity::Arquivo,
                subjectId: $original->id,
                before: ArquivoAuditSnapshot::capture($original),
                subjectLabel: $original->nome_original,
                metadata: [
                    'operation' => 'arquivo_group_delete',
                    'item_acervo' => $this->itemSummary($item),
                    'derivations_removed' => ArquivoAuditSnapshot::summaries($derivations),
                ],
            );

            return;
        }

        foreach ($arquivos as $arquivo) {
            $audit->capture(
                action: AuditAction::Deleted,
                subjectType: AuditEntity::Arquivo,
                subjectId: $arquivo->id,
                before: ArquivoAuditSnapshot::capture($arquivo),
                subjectLabel: $arquivo->nome_original ?? $arquivo->versao_arquivo,
                metadata: [
                    'operation' => 'arquivo_orphan_delete',
                    'item_acervo' => $this->itemSummary($item),
                ],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function storeOriginalAttributes(
        ItemAcervo $item,
        UploadedFile $file,
        string $sha256,
        StorageMutationJournal $journal,
    ): array {
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $directory = "acervo/originais/{$item->id}";
        $filename = Str::uuid()->toString().'.'.$extension;
        $expectedPath = "{$directory}/{$filename}";
        $journal->created(self::PROVIDER, $expectedPath);
        $path = $this->storage->storeUploaded(
            self::PROVIDER,
            $file,
            $directory,
            $filename,
        );

        if ($path !== $expectedPath) {
            $journal->created(self::PROVIDER, $path);
        }
        [$width, $height] = $this->imageDimensions($file);

        return [
            'nome_original' => $file->getClientOriginalName(),
            'provider' => self::PROVIDER,
            'external_file_id' => null,
            'storage_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'tipo_arquivo' => $this->fileType($file),
            'sha256' => $sha256,
            'versao_arquivo' => 'original',
            'width' => $width,
            'height' => $height,
        ];
    }

    private function hash(UploadedFile $file): string
    {
        return (string) hash_file('sha256', $file->getRealPath());
    }

    private function ensureNotDuplicated(string $sha256, ItemAcervo $item): void
    {
        $duplicate = Arquivo::query()
            ->with(['itemAcervo' => fn ($query) => $query->withTrashed()])
            ->where('sha256', $sha256)
            ->where('versao_arquivo', 'original')
            ->where('item_acervo_id', '!=', $item->id)
            ->oldest('id')
            ->lockForUpdate()
            ->first();

        if (! $duplicate instanceof Arquivo) {
            return;
        }

        $duplicateItem = $duplicate->itemAcervo;
        $message = $duplicateItem instanceof ItemAcervo
            ? "Este arquivo parece já estar cadastrado na fotografia \"{$duplicateItem->titulo}\" (#{$duplicateItem->id})."
            : 'Este arquivo parece já estar cadastrado em outro item do acervo.';

        throw ValidationException::withMessages(['arquivo_original' => $message]);
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function imageDimensions(UploadedFile $file): array
    {
        if (! str_starts_with($file->getMimeType() ?: '', 'image/')) {
            return [null, null];
        }

        $dimensions = @getimagesize($file->getRealPath());

        return [$dimensions[0] ?? null, $dimensions[1] ?? null];
    }

    private function fileType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType() ?: '';

        return match (true) {
            str_starts_with($mimeType, 'image/') => 'imagem',
            $mimeType === 'application/pdf' => 'documento',
            str_starts_with($mimeType, 'audio/') => 'audio',
            str_starts_with($mimeType, 'video/') => 'video',
            default => 'outro',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(
        string $operation,
        ItemAcervo $item,
        OptimizedImageResult $derivations,
    ): array {
        return [
            'operation' => $operation,
            'item_acervo' => $this->itemSummary($item),
            'derivations' => [
                'applicable' => $derivations->applicable,
                'generated' => ArquivoAuditSnapshot::summaries($derivations->generated),
                'failed_versions' => $derivations->failedVersions,
            ],
        ];
    }

    /** @return array{id: int, label: string} */
    private function itemSummary(ItemAcervo $item): array
    {
        return ['id' => (int) $item->id, 'label' => $item->titulo];
    }
}
