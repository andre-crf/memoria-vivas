<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['titulo', 'descricao', 'imagem_capa', 'status'])]
class Colecao extends Model
{
    protected $table = 'colecoes';

    public function itensAcervo(): BelongsToMany
    {
        return $this->belongsToMany(ItemAcervo::class, 'colecao_item_acervo');
    }
}
