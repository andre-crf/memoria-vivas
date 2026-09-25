<?php

namespace App\Services\Acervo;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventCollector;
use App\Auditing\AuditSnapshot;
use App\Auditing\AuditTransaction;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\ItemAcervoAuditSnapshot;
use App\Models\Colecao;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Services\Arquivos\GerenciarArquivoOriginal;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Exclusão física de um item que já está na lixeira. O snapshot completo,
 * incluindo os vínculos, é capturado antes da remoção porque o banco apaga
 * as associações e os registros dependentes por cascade.
 */
final readonly class ExcluirItemAcervoDefinitivamente
{
    public function __construct(
        private AuditTransaction $auditTransaction,
        private GerenciarArquivoOriginal $arquivos,
    ) {}

    public function execute(User $actor, ItemAcervo $item, AuditContext $context): void
    {
        $context->assertActor($actor);
        $journal = $this->arquivos->journal();

        try {
            $this->auditTransaction->run(
                $context,
                function (AuditEventCollector $audit) use ($actor, $item, $journal): void {
                    $item = ItemAcervo::query()
                        ->onlyTrashed()
                        ->lockForUpdate()
                        ->findOrFail($item->getKey());
                    Gate::forUser($actor)->authorize('forceDelete', $item);

                    $before = AuditSnapshot::fromArray([
                        ...ItemAcervoAuditSnapshot::capture($item)->values,
                        ...ItemAcervoAuditSnapshot::captureDeletionState($item)->values,
                    ]);
                    $cascade = $this->cascadeConsequences($item);

                    $audit->capture(
                        action: AuditAction::ForceDeleted,
                        subjectType: AuditEntity::ItemAcervo,
                        subjectId: $item->id,
                        before: $before,
                        subjectLabel: $item->titulo,
                        metadata: [
                            'operation' => 'acervo_item_force_delete',
                            'deletion_type' => 'permanent',
                            'cascade' => $cascade,
                        ],
                    );

                    $this->arquivos->captureDeletion($item, $audit, $journal);
                    $item->forceDelete();
                },
            );
        } catch (Throwable $exception) {
            $journal->rollbackCreated();

            throw $exception;
        }

        $journal->cleanupObsolete();
    }

    /**
     * Registros removidos ou desvinculados pelo banco junto com o item.
     *
     * @return array<string, mixed>
     */
    private function cascadeConsequences(ItemAcervo $item): array
    {
        return [
            'arquivo_ids' => $item->arquivos()->orderBy('id')->pluck('id')->all(),
            'colecao_ids' => $item->colecoes()->orderBy('colecoes.id')->pluck('colecoes.id')->all(),
            'conjunto_contextual_ids' => $item->conjuntosContextuais()
                ->orderBy('conjuntos_contextuais.id')
                ->pluck('conjuntos_contextuais.id')
                ->all(),
            'colecao_capa_ids' => Colecao::query()
                ->where('item_capa_id', $item->getKey())
                ->orderBy('id')
                ->pluck('id')
                ->all(),
            'registro_downloads_count' => $item->registroDownloads()->count(),
        ];
    }
}
