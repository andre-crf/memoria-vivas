<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fuso de exibição
    |--------------------------------------------------------------------------
    |
    | Banco, backend, snapshots de auditoria e o atributo `datetime` do HTML
    | permanecem sempre em UTC. Este fuso é usado apenas para apresentar e para
    | recortar períodos, e funciona como FALLBACK: a preferência é o fuso do
    | navegador, informado pelo parâmetro `fuso` ou pelo cookie
    | `FusoDoUsuario::COOKIE`.
    |
    | Deve ser um identificador IANA (America/Sao_Paulo), nunca um offset fixo
    | (-03:00), para que mudanças de lei e horário de verão sejam respeitadas.
    |
    */
    'fuso_exibicao' => env('APP_DISPLAY_TIMEZONE', 'America/Sao_Paulo'),

    /*
    |--------------------------------------------------------------------------
    | Formatos de exibição
    |--------------------------------------------------------------------------
    */
    'formato_completo' => 'd/m/Y H:i:s',

    'formato_curto' => 'd/m/Y H:i',
];
