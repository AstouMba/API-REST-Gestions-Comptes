<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Documentation generation
    |--------------------------------------------------------------------------
    */
    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                /*
                 |--------------------------------------------------------------------------
                 | Path where to store generated documentation
                 |--------------------------------------------------------------------------
                 */
                'output' => storage_path('api-docs'),

                /*
                 |--------------------------------------------------------------------------
                 | Base URL (important for Render HTTPS)
                 |--------------------------------------------------------------------------
                 */
                'host' => env('APP_URL', 'https://api-rest-gestions-comptes.onrender.com'),
                'basePath' => null,
                'schemes' => ['https'],

                'consumes' => [
                    'application/json',
                ],
                'produces' => [
                    'application/json',
                ],
            ],

            'routes' => [
                /*
                 |--------------------------------------------------------------------------
                 | Route for accessing documentation interface
                 |--------------------------------------------------------------------------
                 */
                'api' => 'api/documentation',

                /*
                 |--------------------------------------------------------------------------
                 | Middleware for documentation route
                 |--------------------------------------------------------------------------
                 */
                'middleware' => [
                    'api',
                ],
            ],

            'paths' => [
                /*
                 |--------------------------------------------------------------------------
                 | Absolute path for assets and json docs
                 |--------------------------------------------------------------------------
                 */
                'use_absolute_path' => true,

                'docs' => storage_path('api-docs'),
                'annotations' => [
                    base_path('app'),
                ],
                'views' => base_path('resources/views/vendor/l5-swagger'),
                'base' => env('L5_SWAGGER_CONST_HOST', 'https://api-rest-gestions-comptes.onrender.com/docs/json'),
                'excludes' => [],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default API version
    |--------------------------------------------------------------------------
    */
    'default_api_version' => 'v1',

    /*
    |--------------------------------------------------------------------------
    | Documentation UI settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'routes' => [
            'api' => 'api/documentation',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Swagger UI Config
    |--------------------------------------------------------------------------
    */
    'swagger_ui' => [
        'displayRequestDuration' => true,
        'filter' => true,
        'docExpansion' => 'none',
    ],
];
