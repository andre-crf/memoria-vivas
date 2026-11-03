<?php

namespace App\Models;

use App\Enums\StatusDerivacoes;
use App\Jobs\GerarVersoesOtimizadas;
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
    'sha256',
    'versao_arquivo',
    'width',
    'height',
    'derivacoes_status',
    'derivacoes_atualizadas_em',
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

    public function isOriginal(): bool
    {
        return $this->versao_arquivo === 'original';
    }

    /**
     * Geração das versões pendente ou em processamento, com atualização recente.
     */
    public function derivacoesEmAndamento(): bool
    {
        return $this->derivacoesAguardando()
            && $this->derivacoes_atualizadas_em?->gt(now()->subSeconds(GerarVersoesOtimizadas::TIMEOUT));
    }

    /**
     * Pendente ou em processamento sem atualização além do tempo limite do job: worker
     * parado, job perdido ou processo encerrado no meio. Pode ser regenerado de novo.
     */
    public function derivacoesSemProgresso(): bool
    {
        return $this->derivacoesAguardando() && ! $this->derivacoesEmAndamento();
    }

    private function derivacoesAguardando(): bool
    {
        return in_array($this->derivacoes_status, [StatusDerivacoes::Pendente, StatusDerivacoes::Processando], true);
    }

    public static function rotuloVersao(string $versao): string
    {
        return [
            'original' => 'Original',
            'large' => 'Grande',
            'medium' => 'Média',
            'thumbnail' => 'Miniatura',
        ][$versao] ?? ucfirst($versao);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'derivacoes_status' => StatusDerivacoes::class,
            'derivacoes_atualizadas_em' => 'datetime',
        ];
    }
}
