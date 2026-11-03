<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_acervo_id',
    'arquivo_id',
    'motivo_download_id',
    'perfil_download_id',
    'pais_id',
    'estado_id',
    'municipio_id',
    'nome',
    'email',
    'finalidade',
    'termos_aceitos_em',
    'created_at',
])]
class RegistroDownload extends Model
{
    public $timestamps = false;

    public function itemAcervo(): BelongsTo
    {
        return $this->belongsTo(ItemAcervo::class)->withTrashed();
    }

    public function arquivo(): BelongsTo
    {
        return $this->belongsTo(Arquivo::class);
    }

    public function motivoDownload(): BelongsTo
    {
        return $this->belongsTo(MotivoDownload::class);
    }

    public function perfilDownload(): BelongsTo
    {
        return $this->belongsTo(PerfilDownload::class);
    }

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'termos_aceitos_em' => 'datetime',
        ];
    }
}
