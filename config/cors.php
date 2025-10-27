<?php

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
'paths' => ['api/*', 'sanctum/csrf-cookie', 'docs/*'],

    'allowed_methods' => ['*'],

    // Autorise uniquement les origines locales et ton domaine Render
    'allowed_origins' => [
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'https://api-rest-gestions-comptes.onrender.com',
    ],

    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
