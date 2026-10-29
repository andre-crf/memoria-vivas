<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Ordenação por coluna das listagens administrativas.
 *
 * Só as chaves da lista permitida chegam ao banco: cada uma aponta para uma coluna
 * fixa ou para um Closure; o texto da requisição nunca entra no SQL. Valores fora da
 * lista voltam ao padrão, como os filtros das listagens.
 */
final readonly class OrdenacaoListagem
{
    /**
     * @param  array<string, string|Closure(Builder, string): mixed>  $permitidas
     * @param  list<string>  $comecamDecrescentes  chaves que começam em ordem decrescente (datas, contagens)
     */
    private function __construct(
        public string $chave,
        public string $direcao,
        private array $permitidas,
        private string $padrao,
        private string $direcaoPadrao,
        private array $comecamDecrescentes,
    ) {}

    /**
     * @param  array<string, string|Closure(Builder, string): mixed>  $permitidas
     * @param  list<string>  $comecamDecrescentes
     */
    public static function fromRequest(
        Request $request,
        array $permitidas,
        string $padrao,
        string $direcaoPadrao = 'asc',
        array $comecamDecrescentes = [],
    ): self {
        $chave = $request->query('ordem');
        $direcao = $request->query('direcao');
        $chave = is_string($chave) && array_key_exists($chave, $permitidas) ? $chave : $padrao;
        $direcao = in_array($direcao, ['asc', 'desc'], true)
            ? $direcao
            : ($chave === $padrao ? $direcaoPadrao : (in_array($chave, $comecamDecrescentes, true) ? 'desc' : 'asc'));

        return new self($chave, $direcao, $permitidas, $padrao, $direcaoPadrao, $comecamDecrescentes);
    }

    /**
     * Ordena pela coluna (ou Closure) da chave e desempata pelo ID, para a paginação ser estável.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function aplicar(Builder $query): Builder
    {
        $criterio = $this->permitidas[$this->chave];

        if ($criterio instanceof Closure) {
            $criterio($query, $this->direcao);
        } else {
            $query->orderBy($criterio, $this->direcao);
        }

        return $query->orderBy($query->getModel()->getQualifiedKeyName(), $this->direcao);
    }

    /**
     * Parâmetros da URL; vazios quando a ordenação é a padrão.
     *
     * @return array<string, string>
     */
    public function parametros(): array
    {
        return $this->chave === $this->padrao && $this->direcao === $this->direcaoPadrao
            ? []
            : ['ordem' => $this->chave, 'direcao' => $this->direcao];
    }

    /**
     * Parâmetros do link do cabeçalho: a coluna ativa inverte a direção; outra coluna começa na direção natural.
     *
     * @return array<string, string>
     */
    public function linkPara(string $chave): array
    {
        $direcao = $this->proximaDirecao($chave);

        return $chave === $this->padrao && $direcao === $this->direcaoPadrao ? [] : ['ordem' => $chave, 'direcao' => $direcao];
    }

    public function proximaDirecao(string $chave): string
    {
        if ($chave === $this->chave) {
            return $this->direcao === 'asc' ? 'desc' : 'asc';
        }

        return in_array($chave, $this->comecamDecrescentes, true) ? 'desc' : 'asc';
    }

    public function ativa(string $chave): bool
    {
        return $chave === $this->chave;
    }

    public function ariaSort(string $chave): string
    {
        return match (true) {
            $chave !== $this->chave => 'none',
            $this->direcao === 'asc' => 'ascending',
            default => 'descending',
        };
    }

    /**
     * Ordena uma coluna enum pela ordem de valores informada (igual no MySQL e no SQLite).
     *
     * @param  list<string>  $valores
     * @return Closure(Builder, string): void
     */
    public static function porValores(string $coluna, array $valores): Closure
    {
        $casos = implode(' ', array_fill(0, count($valores), 'WHEN ? THEN ?'));
        $bindings = collect($valores)->flatMap(fn (string $valor, int $posicao): array => [$valor, $posicao])->all();

        return fn (Builder $query, string $direcao) => $query->orderByRaw(
            "CASE {$coluna} {$casos} ELSE ".count($valores)." END {$direcao}",
            $bindings,
        );
    }
}
