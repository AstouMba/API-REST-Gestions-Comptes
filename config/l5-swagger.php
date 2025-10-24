<?php

return [
    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'API Gestion Comptes',
            ],

            'routes' => [
                'api' => 'api/documentation', // interface Swagger UI
            ],

            'paths' => [
                // Assure que toutes les URLs (assets, JS, CSS) utilisent HTTPS
                'use_absolute_path' => true,

                // Répertoire des fichiers Swagger UI
                'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),

                // Fichier JSON généré
                'docs_json' => 'swagger.json',

                // Fichier YAML généré (facultatif)
                'docs_yaml' => 'api-docs.yaml',

                // Format utilisé par défaut
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),

                // Répertoires scannés pour les annotations
                'annotations' => [
                    base_path('app'),
                ],

                // Base de ton API (très important pour Swagger UI)
                'base' => env('APP_URL', 'https://api-rest-gestions-comptes.onrender.com'),
            ],

            'swagger-ui' => [
                'url' => env('APP_URL') . '/docs/asset/',
            ],
        ],
    ],

    'defaults' => [
        'routes' => [
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',

            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],

            'group_options' => [],
        ],

        'paths' => [
            'docs' => storage_path('api-docs'),
            'views' => base_path('resources/views/vendor/l5-swagger'),
            'base' => env('L5_SWAGGER_BASE_PATH', '/api/v1'),
            'excludes' => [],
        ],

        'scanOptions' => [
            'exclude' => [],
            'open_api_spec_version' => env('L5_SWAGGER_OPEN_API_SPEC_VERSION', \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION),
        ],

        'securityDefinitions' => [
            'securitySchemes' => [
                'sanctum' => [
                    'type' => 'apiKey',
                    'description' => 'Utilisez le format Bearer <token>',
                    'name' => 'Authorization',
                    'in' => 'header',
                ],
            ],
            'security' => [
                ['sanctum' => []],
            ],
        ],

        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', true),
        'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),
        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),
        'validator_url' => null,

        'ui' => [
            'display' => [
                'dark_mode' => env('L5_SWAGGER_UI_DARK_MODE', false),
                'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter' => env('L5_SWAGGER_UI_FILTERS', true),
            ],

            'authorization' => [
                'persist_authorization' => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', false),
                'oauth2' => [
                    'use_pkce_with_authorization_code_grant' => false,
                ],
            ],
        ],

        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('APP_URL', 'https://api-rest-gestions-comptes.onrender.com'),
        ],
    ],
];
