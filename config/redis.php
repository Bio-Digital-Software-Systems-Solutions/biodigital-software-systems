<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Redis Connection
    |--------------------------------------------------------------------------
    |
    | This option controls the default connection that will be used when you
    | interact with Redis. You may set this to any connection defined in the
    | "connections" array below.
    |
    */

    'default' => env('REDIS_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Redis Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Redis connections used by your application.
    | Note that the default connection is shared by the cache, queue,
    | and session drivers.
    |
    */

    'connections' => [

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'read_timeout' => 60,
            'context' => [
                // ...
            ],
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'read_timeout' => 60,
            'context' => [
                // ...
            ],
        ],

        'session' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '2'),
            'read_timeout' => 60,
            'context' => [
                // ...
            ],
        ],

        'queue' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_QUEUE_DB', '3'),
            'read_timeout' => 60,
            'context' => [
                // ...
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Client
    |--------------------------------------------------------------------------
    |
    | Here you may specify which client you wish to use when utilizing Redis
    | in your application. Laravel supports both "phpredis" and "predis" as
    | Redis clients.
    |
    | "phpredis" is a PHP extension and is recommended for production use.
    | "predis" is a pure PHP Redis client.
    |
    */

    'client' => env('REDIS_CLIENT', 'predis'),

    /*
    |--------------------------------------------------------------------------
    | Redis Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify global options for the Redis client. These options
    | are used to configure the underlying client instance.
    |
    */

    'options' => extension_loaded('redis') && env('REDIS_CLIENT', 'phpredis') === 'phpredis' ? [
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        'serializer' => Redis::SERIALIZER_PHP, // Better performance than igbinary
        //'compression' => Redis::COMPRESSION_LZ4, // Compress data for better memory usage
    ] : [
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],

];
