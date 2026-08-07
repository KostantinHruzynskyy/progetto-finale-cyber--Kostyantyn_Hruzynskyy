<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiter Defaults (Impostazioni Predefinite)
    |--------------------------------------------------------------------------
    |
    | Qui puoi definire i limiti di richieste predefiniti e il numero massimo
    | di tentativi consentiti per ogni rate limiter specifico.
    |
    */

    'defaults' => [
        'limit' => env('RATE_LIMITER_GLOBAL', 100),
        'decay' => 1, // minuti
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Rate Limiters (Rate Limiter Personalizzati)
    |--------------------------------------------------------------------------
    |
    | Qui puoi definire rate limiter personalizzati per route o azioni specifiche.
    | Ogni rate limiter è definito come coppia chiave-valore dove la chiave è il
    | nome del rate limiter e il valore è un array con 'limit' e 'decay'.
    |
    */

    'limiters' => [
        // Route di ricerca - 30 richieste al minuto
        'search' => [
            'limit' => env('RATE_LIMITER_SEARCH', 30),
            'decay' => 1,
        ],

        // Invio candidature - 5 richieste al minuto
        'careers' => [
            'limit' => env('RATE_LIMITER_CAREERS', 5),
            'decay' => 1,
        ],

        // Tentativi di login - 5 richieste al minuto (default di Fortify)
        'login' => [
            'limit' => 5,
            'decay' => 1,
        ],

        // Autenticazione a due fattori - 5 richieste al minuto
        'two-factor' => [
            'limit' => 5,
            'decay' => 1,
        ],

        // Route API - 60 richieste al minuto
        'api' => [
            'limit' => 60,
            'decay' => 1,
        ],
    ],

];
