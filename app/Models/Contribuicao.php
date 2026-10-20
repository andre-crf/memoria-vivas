<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'email', 'titulo', 'historia', 'local', 'periodo', 'autorizacao'])]
class Contribuicao extends Model
{
    protected $table = 'contribuicoes';

    public function arquivos(): HasMany
    {
        return $this->hasMany(ContribuicaoArquivo::class);
    }
}
