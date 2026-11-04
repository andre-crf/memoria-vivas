<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['titulo', 'descricao'])]
class ConjuntoContextual extends Model
{
    protected $table = 'conjuntos_contextuais';

    public function itensAcervo(): BelongsToMany
    {
        return $this->belongsToMany(ItemAcervo::class, 'conjunto_contextual_item_acervo')
            ->withPivot('ordem');
    }
}
