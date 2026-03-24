<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain & Path
    |--------------------------------------------------------------------------
    | Horizon is accessible at /horizon.  Restrict access via HorizonServiceProvider
    | or the 'gate' callback below.
    */

    'domain' => env('HORIZON_DOMAIN'),
    'path'   => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'kinaya'), '_') . '_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds (seconds)
    |--------------------------------------------------------------------------
    | Alert when job has been waiting longer than these thresholds.
    */

    'waits' => [
        'redis:default'  => 60,
        'redis:webhooks' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times (minutes)
    |--------------------------------------------------------------------------
    */

    'trim' => [
        'recent'        => 60,
        'pending'       => 60,
        'completed'     => 180,
        'recent_failed' => 10080,   // 7 days
        'failed'        => 10080,
        'monitored'     => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    | These jobs will not appear in the Horizon dashboard.
    */

    'silenced' => [],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */

    'metrics' => [
        'trim_snapshots' => [
            'job'   => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    */

    'memory_limit' => 128,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Two supervisor groups:
    |   "worker"   — processes the 'default' queue (QRIS charge jobs)
    |   "webhooks" — processes the 'webhooks' queue (Midtrans notifications)
    |
    | The 'webhooks' supervisor runs MORE processes so notifications are
    | handled quickly even during charge bursts.
    */

    'environments' => [

        'production' => [

            'worker' => [
                'connection'  => 'redis',
                'queue'       => ['default'],
                'balance'     => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 4,
                'minProcesses' => 1,
                'maxTime'      => 0,
                'maxJobs'      => 0,
                'memory'       => 128,
                'tries'        => 3,
                'timeout'      => 60,
                'nice'         => 0,
            ],

            'webhooks' => [
                'connection'  => 'redis',
                'queue'       => ['webhooks'],
                'balance'     => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 6,
                'minProcesses' => 2,
                'maxTime'      => 0,
                'maxJobs'      => 0,
                'memory'       => 128,
                'tries'        => 5,
                'timeout'      => 30,
                'nice'         => 0,
            ],

        ],

        'local' => [

            'worker' => [
                'connection'  => 'redis',
                'queue'       => ['default'],
                'balance'     => 'simple',
                'maxProcesses' => 2,
                'minProcesses' => 1,
                'tries'        => 3,
                'timeout'      => 60,
            ],

            'webhooks' => [
                'connection'  => 'redis',
                'queue'       => ['webhooks'],
                'balance'     => 'simple',
                'maxProcesses' => 2,
                'minProcesses' => 1,
                'tries'        => 5,
                'timeout'      => 30,
            ],

        ],

    ],

];
