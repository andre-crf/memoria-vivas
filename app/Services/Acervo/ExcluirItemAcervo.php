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
use Illuminate\Support\Facades\Gate;

/**
 * Exclusão lógica: o item vai para a lixeira e mantém seus vínculos.
 */
final readonly class ExcluirItemAcervo
{
    public function __construct(
        private AuditTransaction $auditTransaction,
    ) {}

    public function execute(User $actor, ItemAcervo $item, AuditContext $context): ItemAcervo
    {
        $context->assertActor($actor);

        return $this->auditTransaction->run(
            $context,
            function (AuditEventCollector $audit) use ($actor, $item): ItemAcervo {
                $item = ItemAcervo::query()->lockForUpdate()->findOrFail($item->getKey());
                Gate::forUser($actor)->authorize('delete', $item);

                $before = ItemAcervoAuditSnapshot::capture($item);
                $item->delete();

                $audit->capture(
                    action: AuditAction::Deleted,
                    subjectType: AuditEntity::ItemAcervo,
                    subjectId: $item->id,
                    before: $before,
                    subjectLabel: $item->titulo,
                    metadata: [
                        'operation' => 'acervo_item_soft_delete',
                        'deletion_type' => 'soft',
                    ],
                );

                return $item;
            },
        );
    }
}
