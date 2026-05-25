<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Action Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where your Action classes are located.
    |
    */
    'namespace' => 'App\\Actions',

    /*
    |--------------------------------------------------------------------------
    | Request Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where your Form Request classes are located.
    |
    */
    'request_namespace' => 'App\\Http\\Requests',

    /*
    |--------------------------------------------------------------------------
    | Data Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where your Data DTO classes are located.
    |
    */
    'data_namespace' => 'App\\Data',

    /*
    |--------------------------------------------------------------------------
    | Record Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where your Record classes are located.
    |
    */
    'record_namespace' => 'App\\Records',

    /*
    |--------------------------------------------------------------------------
    | Auto-discover Actions
    |--------------------------------------------------------------------------
    |
    | Automatically register routes for discovered Actions.
    |
    */
    'auto_discover' => env('ACTIONS_AUTO_DISCOVER', false),

    /*
    |--------------------------------------------------------------------------
    | Route Prefixes
    |--------------------------------------------------------------------------
    |
    | Default prefixes for API and Web routes.
    |
    */
    'prefixes' => [
        'api' => 'api',
        'web' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Groups
    |--------------------------------------------------------------------------
    |
    | Default middleware for API and Web routes.
    |
    */
    'middleware' => [
        'api' => ['api'],
        'web' => ['web'],
    ],
];
