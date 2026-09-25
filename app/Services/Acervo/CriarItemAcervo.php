<?php

namespace App\Services\Acervo;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventCollector;
use App\Auditing\AuditTransaction;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\ItemAcervoAuditSnapshot;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Services\Arquivos\GerenciarArquivoOriginal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Throwable;

final readonly class CriarItemAcervo
{
    public function __construct(
        private AuditTransaction $auditTransaction,
        private SincronizarRelacionamentosItemAcervo $relacionamentos,
        private GerenciarArquivoOriginal $arquivos,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(
        User $actor,
        array $data,
        AuditContext $context,
        ?UploadedFile $arquivoOriginal = null,
    ): ItemAcervo {
        $context->assertActor($actor);
        $journal = $this->arquivos->journal();

        try {
            $item = $this->auditTransaction->run(
                $context,
                function (AuditEventCollector $audit) use ($actor, $data, $arquivoOriginal, $journal): ItemAcervo {
                    Gate::forUser($actor)->authorize('create', ItemAcervo::class);

                    $item = ItemAcervo::create(
                        Arr::only($data, [...ItemAcervoAuditSnapshot::FIELDS, 'autor_id']),
                    );
                    $this->relacionamentos->execute($item, $data);

                    if ($arquivoOriginal instanceof UploadedFile) {
                        $this->arquivos->upload($actor, $item, $arquivoOriginal, $audit, $journal);
                    }

                    $audit->capture(
                        action: AuditAction::Created,
                        subjectType: AuditEntity::ItemAcervo,
                        subjectId: $item->id,
                        after: ItemAcervoAuditSnapshot::capture($item->refresh()),
                        subjectLabel: $item->titulo,
                        metadata: [
                            'operation' => 'acervo_item_create',
                            'tipo_item' => $item->tipo_item,
                        ],
                    );

                    return $item;
                },
            );
        } catch (Throwable $exception) {
            $journal->rollbackCreated();

            throw $exception;
        }

        $journal->cleanupObsolete();

        return $item;
    }
}
