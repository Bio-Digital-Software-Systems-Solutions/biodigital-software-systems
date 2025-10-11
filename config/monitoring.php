<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Performance Monitoring (APM) Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for various APM providers.
    | Enable the provider you want to use by setting the appropriate
    | environment variables.
    |
    */

    'enabled' => env('APM_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | New Relic Configuration
    |--------------------------------------------------------------------------
    */
    'newrelic' => [
        'enabled' => env('NEWRELIC_ENABLED', false),
        'app_name' => env('NEWRELIC_APP_NAME', env('APP_NAME', 'Laravel')),
        'license_key' => env('NEWRELIC_LICENSE_KEY'),
        'transaction_tracer_enabled' => env('NEWRELIC_TRANSACTION_TRACER_ENABLED', true),
        'error_collector_enabled' => env('NEWRELIC_ERROR_COLLECTOR_ENABLED', true),
        'distributed_tracing_enabled' => env('NEWRELIC_DISTRIBUTED_TRACING_ENABLED', true),

        // Thresholds
        'transaction_threshold' => env('NEWRELIC_TRANSACTION_THRESHOLD', 0.5), // seconds
        'slow_sql_threshold' => env('NEWRELIC_SLOW_SQL_THRESHOLD', 0.1), // seconds

        // Custom attributes
        'custom_attributes' => [
            'environment' => env('APP_ENV'),
            'version' => env('APP_VERSION', '1.0.0'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Datadog Configuration
    |--------------------------------------------------------------------------
    */
    'datadog' => [
        'enabled' => env('DATADOG_ENABLED', false),
        'app_name' => env('DATADOG_APP_NAME', env('APP_NAME', 'laravel')),
        'api_key' => env('DATADOG_API_KEY'),
        'agent_host' => env('DATADOG_AGENT_HOST', 'localhost'),
        'agent_port' => env('DATADOG_AGENT_PORT', 8126),
        'env' => env('DATADOG_ENV', env('APP_ENV', 'production')),
        'version' => env('DATADOG_VERSION', env('APP_VERSION', '1.0.0')),
        'service' => env('DATADOG_SERVICE', env('APP_NAME', 'laravel')),

        // Tracing
        'distributed_tracing' => env('DATADOG_DISTRIBUTED_TRACING', true),
        'trace_agent_url' => env('DATADOG_TRACE_AGENT_URL'),

        // Sampling
        'sampling_rate' => env('DATADOG_SAMPLING_RATE', 1.0),

        // Tags
        'global_tags' => [
            'env' => env('APP_ENV'),
            'version' => env('APP_VERSION', '1.0.0'),
            'host' => gethostname(),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Metrics Configuration
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),

        // Performance thresholds
        'thresholds' => [
            'slow_query' => env('METRICS_SLOW_QUERY_THRESHOLD', 100), // ms
            'slow_request' => env('METRICS_SLOW_REQUEST_THRESHOLD', 1000), // ms
            'high_memory' => env('METRICS_HIGH_MEMORY_THRESHOLD', 128), // MB
        ],

        // What to track
        'track' => [
            'requests' => true,
            'queries' => true,
            'jobs' => true,
            'cache' => true,
            'exceptions' => true,
            'memory' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    */
    'health_check' => [
        'enabled' => env('HEALTH_CHECK_ENABLED', true),
        'endpoint' => env('HEALTH_CHECK_ENDPOINT', '/health'),

        'checks' => [
            'database' => true,
            'cache' => true,
            'storage' => true,
            'queue' => true,
        ],
    ],
];
