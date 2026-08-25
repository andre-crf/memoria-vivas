<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['termo'])]
class PalavraChave extends Model
{
    protected $table = 'palavras_chave';

    public function itensAcervo(): BelongsToMany
    {
        return $this->belongsToMany(ItemAcervo::class, 'item_acervo_palavra_chave');
    }
}
