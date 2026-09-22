<?php

namespace App\Services\Acervo;

use App\Auditing\AuditContext;
use App\Auditing\AuditDiff;
use App\Auditing\AuditEventCollector;
use App\Auditing\AuditTransaction;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\ItemAcervoAuditSnapshot;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

final readonly class AtualizarItemAcervo
{
    private const PUBLICATION_FIELDS = ['status', 'visibilidade'];

    public function __construct(
        private AuditTransaction $auditTransaction,
        private SincronizarRelacionamentosItemAcervo $relacionamentos,
    ) {}

    /**
     * Campos e relacionamentos alterados na mesma submissão geram um único
     * evento `updated`, com as diferenças consolidadas.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, ItemAcervo $item, array $data, AuditContext $context): ItemAcervo
    {
        $context->assertActor($actor);

        return $this->auditTransaction->run(
            $context,
            function (AuditEventCollector $audit) use ($actor, $item, $data): ItemAcervo {
                $item = ItemAcervo::query()->lockForUpdate()->findOrFail($item->getKey());
                Gate::forUser($actor)->authorize('update', $item);

                $before = ItemAcervoAuditSnapshot::capture($item);
                $item->fill(Arr::only($data, [...ItemAcervoAuditSnapshot::FIELDS, 'autor_id']));
                $fieldsChanged = $item->isDirty();

                if ($fieldsChanged) {
                    $item->save();
                }

                $relationshipsChanged = $this->relacionamentos->execute($item, $data);

                if ($relationshipsChanged && ! $fieldsChanged) {
                    // Mantém a autoria resumida do item coerente com a alteração.
                    $item->touch();
                }

                $after = ItemAcervoAuditSnapshot::capture($item->refresh());
                $diff = AuditDiff::between($before, $after);

                $audit->capture(
                    action: AuditAction::Updated,
                    subjectType: AuditEntity::ItemAcervo,
                    subjectId: $item->id,
                    before: $before,
                    after: $after,
                    subjectLabel: $item->titulo,
                    metadata: $this->metadataFor($diff),
                );

                return $item;
            },
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataFor(AuditDiff $diff): array
    {
        $relationships = ['autor', ...array_keys(ItemAcervoAuditSnapshot::RELATIONSHIPS)];
        $groups = [];

        if (array_diff($diff->changedFields, self::PUBLICATION_FIELDS, $relationships) !== []) {
            $groups[] = 'descriptive';
        }

        if (array_intersect($diff->changedFields, self::PUBLICATION_FIELDS) !== []) {
            $groups[] = 'publication';
        }

        if (array_intersect($diff->changedFields, $relationships) !== []) {
            $groups[] = 'relationships';
        }

        $metadata = [
            'operation' => 'acervo_item_update',
            'change_groups' => $groups,
        ];

        $relationshipChanges = $this->relationshipChanges($diff);

        if ($relationshipChanges !== []) {
            $metadata['relationship_changes'] = $relationshipChanges;
        }

        return $metadata;
    }

    /**
     * Resume os vínculos adicionados e removidos em cada relacionamento N:N.
     *
     * @return array<string, array{added: list<int>, removed: list<int>}>
     */
    private function relationshipChanges(AuditDiff $diff): array
    {
        $changes = [];

        foreach (array_keys(ItemAcervoAuditSnapshot::RELATIONSHIPS) as $relationship) {
            if (! in_array($relationship, $diff->changedFields, true)) {
                continue;
            }

            $oldIds = array_column($diff->oldValues[$relationship] ?? [], 'id');
            $newIds = array_column($diff->newValues[$relationship] ?? [], 'id');

            $changes[$relationship] = [
                'added' => array_values(array_diff($newIds, $oldIds)),
                'removed' => array_values(array_diff($oldIds, $newIds)),
            ];
        }

        return $changes;
    }
}
