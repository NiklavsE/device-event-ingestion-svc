<?php

declare(strict_types=1);

return [
    'api_key' => env('INGESTION_API_KEY'),

    'rate_limit' => [
        'per_minute' => (int) env('INGESTION_RATE_LIMIT_PER_MINUTE', 120),
    ],

    'dedup' => [
        'ttl_seconds' => (int) env('INGESTION_DEDUP_TTL_SECONDS', 86400),
        'redis_connection' => env('INGESTION_DEDUP_REDIS_CONNECTION', 'default'),
        'key_prefix' => env('INGESTION_DEDUP_KEY_PREFIX', 'dedup:event:'),
    ],

    'query' => [
        'default_limit' => 100,
        'max_limit' => 500,
    ],
];
