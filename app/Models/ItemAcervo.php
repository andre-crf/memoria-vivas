<?php

namespace App\Models;

use App\Enums\TipoData;
use App\Enums\Visibilidade;
use App\Observers\ItemAcervoObserver;
use App\Support\DataHistorica;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
    'localizacao_fisica',
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

    public function arquivoDaVersao(string $versao): ?Arquivo
    {
        if ($this->relationLoaded('arquivos')) {
            return $this->arquivos->firstWhere('versao_arquivo', $versao);
        }

        return $this->arquivos()
            ->where('versao_arquivo', $versao)
            ->first();
    }

    /**
     * @param  array<int, string>  $versoes
     */
    public function imagemAdministrativa(array $versoes): ?Arquivo
    {
        foreach ($versoes as $versao) {
            $arquivo = $this->arquivoDaVersao($versao);

            if ($arquivo?->isImagem()) {
                return $arquivo;
            }
        }

        return null;
    }

    public function thumbnailAdministrativa(): ?Arquivo
    {
        return $this->imagemAdministrativa(['thumbnail']);
    }

    public function visualizacaoAdministrativa(): ?Arquivo
    {
        return $this->imagemAdministrativa(['large', 'medium', 'thumbnail']);
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

    /**
     * Ordena pela data histórica, com uma regra única para todas as precisões:
     *
     * - a chave é o início do período (ano ou década, mês e dia; o que falta vale 0),
     *   então "década de 1960" vem antes de "1965", que vem antes de "03/1965";
     * - em datas iguais, a mais precisa vem primeiro (exata, mês/ano, ano, década);
     * - itens sem data ficam sempre no fim, nas duas direções.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordenadoPorDataHistorica(Builder $query, string $direcao = 'asc'): void
    {
        $direcao = $direcao === 'desc' ? 'desc' : 'asc';
        // "decada + 0" converte o texto da década em número no MySQL e no SQLite.
        $chave = "CASE WHEN tipo_data = 'desconhecida' OR (ano IS NULL AND decada IS NULL) THEN NULL"
            .' ELSE COALESCE(ano, decada + 0) * 10000 + COALESCE(mes, 0) * 100 + COALESCE(dia, 0) END';
        $precisao = collect(TipoData::cases())
            ->map(fn (TipoData $tipo, int $posicao): string => "WHEN '{$tipo->value}' THEN {$posicao}")
            ->join(' ');

        $query
            ->orderByRaw("({$chave}) IS NULL")
            ->orderByRaw("{$chave} {$direcao}")
            ->orderByRaw("CASE tipo_data {$precisao} END");
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
        return ! $this->trashed() && $this->isPublicado() && $this->isPublico();
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
