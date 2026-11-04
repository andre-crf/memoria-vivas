<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['titulo', 'descricao', 'item_capa_id', 'status'])]
class Colecao extends Model
{
    protected $table = 'colecoes';

    public function itensAcervo(): BelongsToMany
    {
        return $this->belongsToMany(ItemAcervo::class, 'colecao_item_acervo');
    }

    public function itemCapa(): BelongsTo
    {
        return $this->belongsTo(ItemAcervo::class, 'item_capa_id');
    }
}
