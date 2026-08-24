<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['titulo', 'legenda'])]
class ItemAcervo extends Model
{
    use SoftDeletes;

    public function arquivos(): HasMany
    {
        return $this->hasMany(Arquivo::class);
    }
}
