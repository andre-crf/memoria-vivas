<?php

namespace App\Observers;

use App\Models\ItemAcervo;
use Illuminate\Support\Facades\Auth;

/**
 * Preenche a auditoria do item de acervo com o usuário autenticado.
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
}
