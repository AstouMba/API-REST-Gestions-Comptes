<?php

return [
    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'API Gestion Comptes',
            ],

            'routes' => [
                'api'  => 'api/documentation',
                'docs' => 'docs/json',
            ],

            'paths' => [
                'docs' => storage_path('api-docs'),
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => 'json',
                'annotations' => [
                    base_path('app'),
                ],
            ],
        ],
    ],

    // ✅ Configuration pour Render (HTTPS)
    'paths' => [
        'use_absolute_path' => false,
        'docs_json' => 'api-docs.json',
        'docs_yaml' => 'api-docs.yaml',
        'annotations' => base_path('app'),
        'excludes' => [],
        'base' => '/api/v1',
        'views' => base_path('resources/views/vendor/l5-swagger'),
    ],

    // ✅ Force HTTPS derrière proxy Render
    'proxy' => true,
    'secure' => true,

    'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
    'swagger_version' => env('L5_SWAGGER_VERSION', '3.0'),
    'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),
    'validator_url' => null,
];
