<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resolve o fuso em que as datas da requisição serão exibidas e os períodos
 * recortados, garantindo que tela e filtro usem sempre o mesmo fuso.
 *
 * Precedência: parâmetro `fuso` da requisição, cookie escrito pelo navegador
 * e, por fim, o fuso configurado em `datas.fuso_exibicao`. Valores inválidos
 * são descartados em silêncio e recaem no nível seguinte.
 */
final readonly class FusoDoUsuario
{
    /**
     * Nome do cookie escrito pelo navegador.
     *
     * É uma constante, e não configuração, porque `bootstrap/app.php` precisa
     * do mesmo nome para dispensá-lo da criptografia de cookies, e lá a
     * configuração ainda não foi carregada. Dois valores que precisam coincidir
     * e podem divergir fariam o cookie voltar a ser descartado em silêncio.
     */
    public const COOKIE = 'fuso_usuario';

    public function __construct(
        private DataExibicao $padrao,
    ) {}

    public function para(?Request $request): DataExibicao
    {
        if ($request === null) {
            return $this->padrao;
        }

        return $this->padrao
            ->comFuso($this->doCookie($request))
            ->comFuso($this->texto($request->input('fuso')));
    }

    /**
     * O cookie não é criptografado e chega como o navegador o escreveu, então
     * seu conteúdo é dado do cliente: só sobrevive se for um identificador
     * IANA conhecido, validado por `DataExibicao::comFuso()`.
     */
    private function doCookie(Request $request): ?string
    {
        $valor = $this->texto($request->cookie(self::COOKIE));

        // `document.cookie` escreve o valor percent-encoded. O PHP normalmente
        // já decodifica, mas decodificar de novo é inofensivo: identificadores
        // IANA não contêm `%`.
        return $valor === null ? null : rawurldecode($valor);
    }

    private function texto(mixed $valor): ?string
    {
        return is_string($valor) && $valor !== '' ? $valor : null;
    }
}
