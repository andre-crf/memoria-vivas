<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Campos sensíveis
    |--------------------------------------------------------------------------
    |
    | Estes campos são removidos recursivamente antes que qualquer payload seja
    | persistido. Models auditáveis ainda deverão declarar listas positivas de
    | campos permitidos; esta lista funciona como uma segunda camada de defesa.
    |
    */
    'sensitive_fields' => [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'client_secret',
        'private_key',
        'api_key',
        'authorization',
        'cookie',
    ],
];
