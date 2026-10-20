<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nome_original', 'caminho', 'mime_type', 'tamanho'])]
class ContribuicaoArquivo extends Model
{
    protected $table = 'contribuicao_arquivos';

    public function contribuicao(): BelongsTo
    {
        return $this->belongsTo(Contribuicao::class);
    }
}
