<?php

namespace App\Queries\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Pesquisa por trecho de texto com LIKE, tratando % e _ digitados como texto.
 */
trait PesquisaTextual
{
    /**
     * Acrescenta ao grupo uma condição "contém o termo" por coluna, unidas por OR.
     * Use dentro de um where(fn ...) para não misturar o OR com os demais filtros.
     *
     * @param  list<string>  $colunas
     */
    protected function contemTermo(Builder $grupo, array $colunas, string $termo): void
    {
        $padrao = $this->padraoDeBusca($termo);

        foreach ($colunas as $coluna) {
            $grupo->orWhereRaw("{$coluna} LIKE ? ESCAPE '!'", [$padrao]);
        }
    }

    protected function padraoDeBusca(string $termo): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $termo).'%';
    }
}
