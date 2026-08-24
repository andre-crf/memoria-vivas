<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_acervo_id',
    'nome_original',
    'provider',
    'external_file_id',
    'storage_path',
    'mime_type',
    'file_size',
    'tipo_arquivo',
])]
class Arquivo extends Model
{
    public function itemAcervo(): BelongsTo
    {
        return $this->belongsTo(ItemAcervo::class);
    }

    public function isImagem(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isDocumento(): bool
    {
        return $this->mime_type === 'application/pdf' || $this->tipo_arquivo === 'documento';
    }
}
