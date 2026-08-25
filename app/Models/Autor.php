<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'tipo', 'observacao'])]
class Autor extends Model
{
    protected $table = 'autores';

    public function itensAcervo(): HasMany
    {
        return $this->hasMany(ItemAcervo::class);
    }
}
