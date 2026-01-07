<?php

use OpenTelemetry\SemConv\ResourceAttributes;

return [
    /*
    |--------------------------------------------------------------------------
    | Common Configuration
    |--------------------------------------------------------------------------
    |
    */
    'common' => [
        'batch_export_interval' => env('OTL_METRICS_BATCH_EXPORT_INTERVAL', 60),
        'instrument_cache_limit' => env('OTL_METRICS_INSTRUMENT_CACHE_LIMIT', 1000),
        'timeout' => env('OTL_METRICS_TIMEOUT', 10),
        'suppress_exceptions' => env('OTL_METRICS_SUPPRESS_EXCEPTIONS', true),
    ],

    'attributes' => [
        /*
        |--------------------------------------------------------------------------
        | Required Resource Attributes
        |--------------------------------------------------------------------------
        | These attributes are required for OpenTelemetry SDK initialization.
        */
        ResourceAttributes::SERVICE_NAME => env('OTEL_SERVICE_NAME', env('APP_NAME')),
        ResourceAttributes::SERVICE_NAMESPACE => env('OTEL_SERVICE_NS', 'default'),
        ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => env('OTEL_SERVICE_ENV', env('APP_ENV', 'local')),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration (Web)
    |--------------------------------------------------------------------------
    |
    */
    'api' => [
        'enabled' => env('OTL_METRICS_API_ENABLED', true),
        'endpoint' => env('OTL_METRICS_API_PUSH_ENDPOINT', 'http://localhost:4318/v1/metrics'),
        'protocol' => env('OTL_METRICS_API_PUSH_PROTOCOL', 'http/protobuf'),
        'headers' => [],
        'log_channel' => env('OTL_METRICS_API_LOG_CHANNEL', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for queue workers
    |
    */
    'worker' => [
        'enabled' => env('OTL_METRICS_WORKER_ENABLED', true),
        'endpoint' => env('OTL_METRICS_WORKER_PUSH_ENDPOINT', 'http://localhost:4318/v1/metrics'),
        'protocol' => env('OTL_METRICS_WORKER_PUSH_PROTOCOL', 'http/protobuf'),
        'headers' => [],
        'log_channel' => env('OTL_METRICS_WORKER_LOG_CHANNEL', null),
        'track_jobs' => env('OTL_METRICS_WORKER_TRACK_JOBS', true),

        'features' => [
            'track_job_global_state_count' => env('OTLP_METRICS_JOB_COUNT', true),
            'track_job_global_timing' => env('OTLP_METRICS_JOB_TIMING', true),
        ],

        'horizon' => [
            'enabled' => env('OTL_METRICS_HORIZON_ENABLED', true),
            'collection_interval' => env('OTL_METRICS_HORIZON_COLLECTION_INTERVAL', 15), // seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CronJob Configuration
    |--------------------------------------------------------------------------
    |
    */
    'cronjob' => [
        'enabled' => env('OTL_METRICS_CRONJOB_ENABLED', true),
        'endpoint' => env('OTL_METRICS_CRONJOB_PUSH_ENDPOINT', 'http://localhost:4318/v1/metrics'),
        'protocol' => env('OTL_METRICS_CRONJOB_PUSH_PROTOCOL', 'http/protobuf'),
        'headers' => [],
        'log_channel' => env('OTL_METRICS_CRONJOB_LOG_CHANNEL', null),
        'track_scheduled_tasks' => env('OTL_METRICS_CRONJOB_TRACK_SCHEDULED_TASKS', true),
        'auto_detect_context' => env('OTL_METRICS_CRONJOB_AUTO_DETECT', true),
        'force_context' => env('OTL_METRICS_CRONJOB_FORCE_CONTEXT', null), // 'cronjob' or null

        'features' => [
            'track_scheduled_task_global_state_count' => env('OTLP_METRICS_SCHEDULED_TASK_COUNT', true),
            'track_scheduled_task_global_timing' => env('OTLP_METRICS_SCHEDULED_TASK_TIMING', true),
        ],
    ],
];
