<?php

return [
    'schema_version' => 1,
    'default_seed' => 20260809,
    'timeout_seconds' => (int) env('BENCHMARK_TIMEOUT_SECONDS', 90),
    'cooldown_seconds' => (int) env('BENCHMARK_COOLDOWN_SECONDS', 10),
    'persistent_chromium_url' => env('PERSISTENT_CHROMIUM_URL', 'http://chromium-persistent:9222'),
    'gotenberg_metrics_url' => env('GOTENBERG_METRICS_URL', 'http://gotenberg:9464/metrics'),
    'compose_project' => env('BENCHMARK_COMPOSE_PROJECT', 'laravel-pdf-benchmark'),
    'review_enabled' => env('BENCHMARK_REVIEW_ENABLED', false),
    'paths' => [
        'runs' => base_path('results/runs'),
        'published' => base_path('results/published'),
        'work' => base_path('results/work'),
    ],
    'assets' => [
        'logo' => public_path('images/benchmark-logo.png'),
        'font' => storage_path('app/fonts/inter.woff2'),
    ],
    'required_metadata' => [
        'host_label' => env('BENCHMARK_HOST_LABEL'),
        'region' => env('BENCHMARK_REGION'),
        'host_provider' => env('BENCHMARK_HOST_PROVIDER'),
        'host_instance_type' => env('BENCHMARK_HOST_INSTANCE_TYPE'),
        'host_cpu_allocation' => env('BENCHMARK_HOST_CPU_ALLOCATION'),
        'host_purpose' => env('BENCHMARK_HOST_PURPOSE'),
        'host_vcpu' => env('BENCHMARK_HOST_VCPU'),
        'host_memory_mib' => env('BENCHMARK_HOST_MEMORY_MIB'),
        'cloudflare_plan' => env('BENCHMARK_CLOUDFLARE_PLAN'),
        'bladepdf_plan' => env('BENCHMARK_BLADEPDF_PLAN'),
        'bladepdf_concurrency' => env('BENCHMARK_BLADEPDF_CONCURRENCY'),
    ],
    'renderers' => [
        'dompdf' => ['driver' => 'dompdf', 'core' => true, 'readiness' => false, 'external' => false, 'topology' => 'application-process', 'cold_label' => 'fresh worker'],
        'browsershot' => ['driver' => 'browsershot', 'core' => true, 'readiness' => true, 'external' => false, 'topology' => 'application-descendant', 'cold_label' => 'fresh browser per render'],
        'gotenberg' => ['driver' => 'gotenberg', 'core' => true, 'readiness' => true, 'external' => false, 'topology' => 'local-render-service', 'cold_label' => 'first request after controlled container restart'],
        'cloudflare' => ['driver' => 'cloudflare', 'core' => true, 'readiness' => false, 'external' => true, 'topology' => 'external-managed-provider', 'cold_label' => 'first observed API request'],
        'bladepdf' => ['driver' => 'bladepdf', 'core' => true, 'readiness' => true, 'external' => true, 'topology' => 'external-managed-provider', 'cold_label' => 'first observed API request'],
        'browsershot-persistent' => ['driver' => 'browsershot-persistent', 'core' => false, 'readiness' => true, 'external' => false, 'topology' => 'local-render-service', 'cold_label' => 'first connection to persistent browser'],
    ],
    'templates' => [
        'simple-invoice' => ['view' => 'benchmarks.simple-invoice', 'performance' => true, 'expected_pages' => 2],
        'modern-invoice' => ['view' => 'benchmarks.modern-invoice', 'performance' => true, 'expected_pages' => 2],
        'long-report' => ['view' => 'benchmarks.long-report', 'performance' => false, 'expected_pages' => 10],
        'javascript-chart' => ['view' => 'benchmarks.javascript-chart', 'performance' => false, 'expected_pages' => 1],
        'local-assets' => ['view' => 'benchmarks.local-assets', 'performance' => false, 'expected_pages' => 1],
    ],
    'profiles' => [
        'smoke' => [
            ['slug' => 'first', 'phase' => 'first', 'iterations' => 1, 'concurrency' => 1, 'measured' => true],
            ['slug' => 'sequential', 'phase' => 'measured', 'iterations' => 2, 'concurrency' => 1, 'measured' => true],
        ],
        'full' => [
            ['slug' => 'first', 'phase' => 'first', 'iterations' => 1, 'concurrency' => 1, 'measured' => true],
            ['slug' => 'warmup', 'phase' => 'warmup', 'iterations' => 5, 'concurrency' => 1, 'measured' => false],
            ['slug' => 'sequential', 'phase' => 'measured', 'iterations' => 50, 'concurrency' => 1, 'measured' => true],
            ['slug' => 'concurrency-5', 'phase' => 'measured', 'iterations' => 100, 'concurrency' => 5, 'measured' => true],
            ['slug' => 'concurrency-10', 'phase' => 'measured', 'iterations' => 100, 'concurrency' => 10, 'measured' => true],
        ],
        'capacity' => [
            ['slug' => 'first', 'phase' => 'first', 'iterations' => 1, 'concurrency' => 1, 'measured' => true],
            ['slug' => 'warmup', 'phase' => 'warmup', 'iterations' => 5, 'concurrency' => 1, 'measured' => false],
        ],
        'fidelity' => [
            ['slug' => 'representative', 'phase' => 'fidelity', 'iterations' => 1, 'concurrency' => 1, 'measured' => false],
        ],
    ],
    'capacity' => [
        'concurrency_levels' => [1, 2, 4, 8, 12, 16],
        'iterations' => 100,
        'maximum_concurrency' => 64,
        'maximum_iterations' => 10000,
    ],
    'safe_response_headers' => [
        'content-length', 'cf-ray', 'retry-after', 'server-timing', 'x-browser-ms-used', 'x-request-id',
    ],
    'fidelity_features' => [
        'simple-invoice' => [
            ['slug' => 'table-layout', 'label' => 'Table layout', 'page' => 1, 'crop' => [.05, .28, .90, .48]],
            ['slug' => 'manual-page-break', 'label' => 'Two-page manual break', 'page' => 1, 'crop' => [0, 0, 1, 1]],
            ['slug' => 'portable-png', 'label' => 'Portable PNG logo', 'page' => 1, 'crop' => [.05, .03, .35, .12]],
        ],
        'modern-invoice' => [
            ['slug' => 'gradient-flexbox', 'label' => 'Gradient and Flexbox', 'page' => 1, 'crop' => [.04, .03, .92, .21]],
            ['slug' => 'css-grid', 'label' => 'CSS Grid', 'page' => 1, 'crop' => [.04, .25, .92, .18]],
            ['slug' => 'inline-svg', 'label' => 'Inline SVG', 'page' => 1, 'crop' => [.69, .45, .25, .25]],
            ['slug' => 'custom-font', 'label' => 'Inter WOFF2', 'page' => 1, 'crop' => [.07, .09, .50, .08]],
        ],
        'long-report' => [
            ['slug' => 'ten-pages', 'label' => 'Ten-page chapter pagination', 'page' => 1, 'crop' => [0, 0, 1, 1]],
            ['slug' => 'long-table', 'label' => 'Long table and repeated header', 'page' => 1, 'crop' => [.05, .27, .90, .60]],
            ['slug' => 'chart', 'label' => 'Deterministic chart', 'page' => 1, 'crop' => [.05, .16, .90, .13]],
        ],
        'javascript-chart' => [
            ['slug' => 'javascript-canvas', 'label' => 'JavaScript canvas chart', 'page' => 1, 'crop' => [.08, .25, .84, .50]],
            ['slug' => 'delayed-content', 'label' => 'Delayed readiness content', 'page' => 1, 'crop' => [.08, .77, .84, .10]],
        ],
        'local-assets' => [
            ['slug' => 'local-png', 'label' => 'public_path PNG', 'page' => 1, 'crop' => [.08, .08, .45, .15]],
            ['slug' => 'vite-css', 'label' => 'Vite CSS', 'page' => 1, 'crop' => [.06, .05, .88, .70]],
            ['slug' => 'storage-font', 'label' => 'storage_path WOFF2', 'page' => 1, 'crop' => [.08, .22, .78, .12]],
        ],
    ],
];
