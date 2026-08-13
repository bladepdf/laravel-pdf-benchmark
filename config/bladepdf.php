<?php

declare(strict_types=1);

return [
    'base_url' => env('BLADEPDF_BASE_URL', 'https://api.bladepdf.com'),
    'api_key' => env('BLADEPDF_API_KEY'),
    'webhook_secret' => env('BLADEPDF_WEBHOOK_SECRET'),
    'webhook_tolerance' => 300,
    'timeout' => (int) env('BLADEPDF_TIMEOUT', 60),
    'connect_timeout' => (int) env('BLADEPDF_CONNECT_TIMEOUT', 10),
    // Failures are observations; the harness never hides them behind retries.
    'retry_times' => 1,
    'retry_sleep' => 0,
    'verify_ssl' => true,
    'user_agent' => 'bladepdf-laravel-benchmark/1.0',
    'auto_resolve_assets' => true,
    'local_hosts' => ['localhost', '127.0.0.1', '::1'],
];
