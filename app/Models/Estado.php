<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_ibge', 'sigla', 'nome'])]
class Estado extends Model
{
    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class);
    }

    public function registroDownloads(): HasMany
    {
        return $this->hasMany(RegistroDownload::class);
    }
}
