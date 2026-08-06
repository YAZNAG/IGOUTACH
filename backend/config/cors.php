<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['*'],

    'allowed_methods' => ['*'],

    // Origines autorisées : le front principal (FRONTEND_URL) plus, le cas
    // échéant, une liste séparée par des virgules dans CORS_ALLOWED_ORIGINS
    // (utile pour tester l'app Flutter web depuis un poste de développement).
    'allowed_origins' => array_values(array_filter(array_unique(array_merge(
        [env('FRONTEND_URL', 'http://localhost:3000')],
        array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))),
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
