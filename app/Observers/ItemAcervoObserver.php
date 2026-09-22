<?php

namespace App\Observers;

use App\Models\ItemAcervo;
use Illuminate\Support\Facades\Auth;

/**
 * Mantém somente a autoria resumida do item (`*_by_user_id`) com o usuário
 * autenticado. O histórico de alterações não é responsabilidade deste
 * observer: ele é registrado em `audit_events` pelos serviços de
 * `App\Services\Acervo`.
 *
 * Os campos `*_by_user_id` ficam fora do Fillable justamente para que nenhum
 * cliente da aplicação possa escolher a quem atribuir a ação.
 */
class ItemAcervoObserver
{
    public function creating(ItemAcervo $item): void
    {
        $item->created_by_user_id = Auth::id();
        $item->updated_by_user_id = Auth::id();
    }

    public function updating(ItemAcervo $item): void
    {
        $item->updated_by_user_id = Auth::id();
    }

    public function deleting(ItemAcervo $item): void
    {
        if ($item->isForceDeleting()) {
            return;
        }

        $item->deleted_by_user_id = Auth::id();
        $item->saveQuietly();
    }

    public function restoring(ItemAcervo $item): void
    {
        // O item deixou de estar excluído: quem o excluiu não é mais informação
        // corrente. O `updating` do próprio restore registra quem restaurou.
        $item->deleted_by_user_id = null;
    }
}
