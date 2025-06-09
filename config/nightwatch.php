<?php

return [
    'enabled' => env('NIGHTWATCH_ENABLED', true),
    'token' => env('NIGHTWATCH_TOKEN'),
    'deployment' => env('NIGHTWATCH_DEPLOY'),
    'server' => env('NIGHTWATCH_SERVER', (string) gethostname()),

    'local_ingest' => env('NIGHTWATCH_LOCAL_INGEST', 'socket'), // "socket"|"log"|"null"
    'remote_ingest' => env('NIGHTWATCH_REMOTE_INGEST', 'http'),

    'buffer_threshold' => env('NIGHTWATCH_BUFFER_THRESHOLD', 1_000_000),

    'error_log_channel' => env('NIGHTWATCH_ERROR_LOG_CHANNEL', 'single'),

    'ingests' => [

        'socket' => [
            'uri' => env('NIGHTWATCH_SOCKET_INGEST_URI', '127.0.0.1:2407'),
            'connection_timeout' => env('NIGHTWATCH_SOCKET_INGEST_CONNECTION_TIMEOUT', 0.5),
            'timeout' => env('NIGHTWATCH_SOCKET_INGEST_TIMEOUT', 0.5),
        ],

        'http' => [
            'connection_timeout' => env('NIGHTWATCH_HTTP_INGEST_CONNECTION_TIMEOUT', 5),
            'timeout' => env('NIGHTWATCH_HTTP_INGEST_TIMEOUT', 10),
        ],

        'log' => [
            'channel' => env('NIGHTWATCH_LOG_INGEST_CHANNEL', 'single'),
        ],

    ],
];
