<?php

namespace App\Models;

use App\Enums\TipoData;
use App\Enums\Visibilidade;
use App\Observers\ItemAcervoObserver;
use App\Support\DataHistorica;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'tipo_item',
    'titulo',
    'legenda',
    'dia',
    'mes',
    'ano',
    'decada',
    'tipo_data',
    'local_atual',
    'local_epoca',
    'evento',
    'cedente',
    'estado_conservacao',
    'status',
    'autor_id',
    'visibilidade',
])]
#[ObservedBy(ItemAcervoObserver::class)]
class ItemAcervo extends Model
{
    use SoftDeletes;

    public const ESTADOS_CONSERVACAO = [
        'excelente' => 'Excelente',
        'bom' => 'Bom',
        'regular' => 'Regular',
        'ruim' => 'Ruim',
        'critico' => 'Crítico',
        'desconhecido' => 'Desconhecido',
    ];

    public const STATUS = [
        'rascunho' => 'Rascunho',
        'em_revisao' => 'Em revisão',
        'publicado' => 'Publicado',
        'arquivado' => 'Arquivado',
    ];

    protected $table = 'item_acervos';

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Autor::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function excluidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }

    public function arquivos(): HasMany
    {
        return $this->hasMany(Arquivo::class);
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'categoria_item_acervo');
    }

    public function assuntos(): BelongsToMany
    {
        return $this->belongsToMany(Assunto::class, 'assunto_item_acervo');
    }

    public function palavrasChave(): BelongsToMany
    {
        return $this->belongsToMany(PalavraChave::class, 'item_acervo_palavra_chave');
    }

    public function pessoas(): BelongsToMany
    {
        return $this->belongsToMany(Pessoa::class, 'item_acervo_pessoa');
    }

    public function colecoes(): BelongsToMany
    {
        return $this->belongsToMany(Colecao::class, 'colecao_item_acervo');
    }

    public function conjuntosContextuais(): BelongsToMany
    {
        return $this->belongsToMany(ConjuntoContextual::class, 'conjunto_contextual_item_acervo')
            ->withPivot('ordem');
    }

    public function registroDownloads(): HasMany
    {
        return $this->hasMany(RegistroDownload::class);
    }

    public function dataHistorica(): DataHistorica
    {
        return DataHistorica::deArray($this->only(['tipo_data', 'dia', 'mes', 'ano', 'decada']));
    }

    public function isPublicado(): bool
    {
        return $this->status === 'publicado';
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function estadoConservacaoLabel(): string
    {
        return self::ESTADOS_CONSERVACAO[$this->estado_conservacao] ?? ucfirst((string) $this->estado_conservacao);
    }

    public function isPublico(): bool
    {
        return $this->visibilidade === Visibilidade::Publico;
    }

    public function podeSerExibidoPublicamente(): bool
    {
        return $this->isPublicado() && $this->isPublico();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dia' => 'integer',
            'mes' => 'integer',
            'ano' => 'integer',
            'tipo_data' => TipoData::class,
            'visibilidade' => Visibilidade::class,
        ];
    }
}
