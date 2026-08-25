<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_acervo_id',
    'motivo_download_id',
    'perfil_download_id',
    'pais',
    'estado',
    'cidade',
    'created_at',
])]
class RegistroDownload extends Model
{
    public $timestamps = false;

    public function itemAcervo(): BelongsTo
    {
        return $this->belongsTo(ItemAcervo::class);
    }

    public function motivoDownload(): BelongsTo
    {
        return $this->belongsTo(MotivoDownload::class);
    }

    public function perfilDownload(): BelongsTo
    {
        return $this->belongsTo(PerfilDownload::class);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
