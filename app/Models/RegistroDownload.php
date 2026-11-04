<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_acervo_id',
    'titulo_item',
    'codigo_referencia_item',
    'arquivo_id',
    'versao_arquivo',
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

    // A fotografia pode ter sido excluída definitivamente: usa a identificação
    // gravada no momento do download.
    public function tituloFotografia(): string
    {
        return $this->itemAcervo?->titulo ?? $this->titulo_item ?? 'Fotografia removida';
    }

    public function fotografiaExcluida(): bool
    {
        return $this->item_acervo_id === null;
    }

    // A derivação entregue some quando as versões são regeneradas ou o original é substituído.
    public function rotuloVersaoEntregue(): ?string
    {
        $versao = $this->arquivo?->versao_arquivo ?? $this->versao_arquivo;

        return $versao !== null ? Arquivo::rotuloVersao($versao) : null;
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
