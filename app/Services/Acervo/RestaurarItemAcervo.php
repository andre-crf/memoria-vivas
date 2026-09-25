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

final readonly class RestaurarItemAcervo
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
                $item = ItemAcervo::query()
                    ->onlyTrashed()
                    ->lockForUpdate()
                    ->findOrFail($item->getKey());
                Gate::forUser($actor)->authorize('restore', $item);

                $before = ItemAcervoAuditSnapshot::captureDeletionState($item);
                $item->restore();
                $after = ItemAcervoAuditSnapshot::captureDeletionState($item);

                $audit->capture(
                    action: AuditAction::Restored,
                    subjectType: AuditEntity::ItemAcervo,
                    subjectId: $item->id,
                    before: $before,
                    after: $after,
                    subjectLabel: $item->titulo,
                    metadata: ['operation' => 'acervo_item_restore'],
                );

                return $item;
            },
        );
    }
}
