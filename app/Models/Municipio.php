<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_ibge', 'estado_id', 'nome'])]
class Municipio extends Model
{
    protected $table = 'municipios';

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function registroDownloads(): HasMany
    {
        return $this->hasMany(RegistroDownload::class);
    }
}
