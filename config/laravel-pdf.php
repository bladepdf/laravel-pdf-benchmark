<?php

use Spatie\LaravelPdf\Caching\DefaultPdfCache;
use Spatie\LaravelPdf\Encryption\DefaultPdfEncrypter;
use Spatie\LaravelPdf\Jobs\GeneratePdfJob;

return [
    'driver' => env('LARAVEL_PDF_DRIVER', 'browsershot'),
    'cache' => [
        'class' => DefaultPdfCache::class,
        'automatic' => false,
        'store' => null,
        'prefix' => 'benchmark-disabled',
        'ttl' => null,
    ],
    'browsershot' => [
        'node_binary' => env('LARAVEL_PDF_NODE_BINARY'),
        'npm_binary' => env('LARAVEL_PDF_NPM_BINARY'),
        'include_path' => env('LARAVEL_PDF_INCLUDE_PATH'),
        'chrome_path' => env('LARAVEL_PDF_CHROME_PATH'),
        'node_modules_path' => env('LARAVEL_PDF_NODE_MODULES_PATH', base_path('node_modules')),
        'bin_path' => env('LARAVEL_PDF_BIN_PATH'),
        'temp_path' => env('LARAVEL_PDF_TEMP_PATH', storage_path('framework/benchmark')),
        'write_options_to_file' => true,
        'no_sandbox' => (bool) env('LARAVEL_PDF_NO_SANDBOX', false),
    ],
    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
    ],
    'gotenberg' => [
        'url' => env('GOTENBERG_URL', 'http://gotenberg:3000'),
        'username' => env('GOTENBERG_USERNAME'),
        'password' => env('GOTENBERG_PASSWORD'),
    ],
    'dompdf' => [
        'is_remote_enabled' => false,
        'chroot' => env('LARAVEL_PDF_DOMPDF_CHROOT', base_path()),
    ],
    'job' => GeneratePdfJob::class,
    'encrypter' => DefaultPdfEncrypter::class,
];
