<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['titulo', 'descricao'])]
class Assunto extends Model
{
    protected $table = 'assuntos';

    public function itensAcervo(): BelongsToMany
    {
        return $this->belongsToMany(ItemAcervo::class, 'assunto_item_acervo');
    }
}
