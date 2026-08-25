<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['titulo', 'ativo'])]
class PerfilDownload extends Model
{
    protected $table = 'perfis_download';

    public function registroDownloads(): HasMany
    {
        return $this->hasMany(RegistroDownload::class);
    }

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }
}
