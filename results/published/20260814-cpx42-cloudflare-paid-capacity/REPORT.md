# Laravel PDF Benchmark Report

Run: `20260814-cpx42-cloudflare-paid-capacity`  
Profile: `capacity`  
Generated: 2026-08-15T00:18:50+00:00  
Host label: hetzner-cpx42-budget  
Region: Nuremberg, Germany  
Infrastructure: hetzner CPX42; 8 vCPU (shared); 15608 MiB RAM; purpose: budget.  
Load generation: in-process worker pool, co-located with the application on the benchmark host.  
Git: `228cf7daed95a8fad19845ab89c8d5862bfd7ea5`  
Cloudflare plan: workers-paid-quick-actions; BladePDF plan: scale; declared BladePDF concurrency: 8.

Percentiles: nearest-rank; application cache and retries disabled; Cloudflare Quick Actions cache disabled with `cacheTTL=0`.

> Provider-side resource consumption is not observable, so only application-side usage was measured for managed services.

## Core renderer performance

| Renderer | Variant | Template | Scenario / first-observation label | Concurrency | Success | Fail | Timeout | p50 ms | p95 ms | p99 ms | CV % | Peak in-flight | PDF KiB | Successful PDF/s | Observed CPU % | App peak MiB | Render service peak MiB | Queue peak |
|---|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| cloudflare | default | modern-invoice | first - first observed API request | 1 | 1/1 | 0 | 0 | 483.55 | 483.55 | 483.55 | n/a | 1 | 201.97 | 2.05 | 1.28 | 60.95 | n/a | n/a |
| cloudflare | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 501.63 | 736.47 | 980.20 | 21.11 | 1 | 201.97 | 1.90 | 1.33 | 107.52 | n/a | n/a |
| cloudflare | default | modern-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 476.73 | 669.19 | 944.27 | 23.38 | 2 | 201.97 | 3.86 | 2.50 | 169.58 | n/a | n/a |
| cloudflare | default | modern-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 480.43 | 811.11 | 951.75 | 27.33 | 4 | 201.97 | 6.75 | 3.72 | 288.11 | n/a | n/a |
| cloudflare | default | modern-invoice | concurrency-8 | 8 | 52/100 | 48 | 0 | 486.69 | 656.80 | 818.47 | 18.28 | 8 | 201.97 | 9.57 | 10.16 | 522.56 | n/a | n/a |
| cloudflare | default | simple-invoice | first - first observed API request | 1 | 1/1 | 0 | 0 | 294.28 | 294.28 | 294.28 | n/a | 1 | 77.30 | 3.39 | 2.54 | 60.53 | n/a | n/a |
| cloudflare | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 377.26 | 503.51 | 587.80 | 18.35 | 1 | 77.30 | 2.58 | 1.67 | 74.67 | n/a | n/a |
| cloudflare | default | simple-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 374.62 | 641.88 | 716.97 | 25.23 | 2 | 77.30 | 4.70 | 2.78 | 135.73 | n/a | n/a |
| cloudflare | default | simple-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 358.90 | 627.55 | 778.49 | 27.42 | 4 | 77.30 | 9.37 | 5.47 | 257.91 | n/a | n/a |
| cloudflare | default | simple-invoice | concurrency-8 | 8 | 41/100 | 59 | 0 | 369.43 | 507.54 | 878.71 | 25.45 | 8 | 77.30 | 11.51 | 11.16 | 493.39 | n/a | n/a |

## Capacity sweep observations

These are observed limits, not a CPU-to-concurrency assumption. Throughput peak and first failure level are descriptive; the report does not assign an automatic production-safe concurrency.

| Renderer | Variant | Template | Tested levels | Declared plan limit | Highest failure-free within limit | Throughput peak at | First failure/timeout at |
|---|---|---|---|---:|---:|---:|---:|
| cloudflare | default | modern-invoice | 1, 2, 4, 8 | n/a | 4 | 8 | 8 |
| cloudflare | default | simple-invoice | 1, 2, 4, 8 | n/a | 4 | 8 | 8 |


## Operational methodology

See `operations.json` for the dated, source-linked installation, dependency, asset, and ownership declarations captured with this run.

## Artifacts

See `summary.json`, `summary.csv`, `raw/`, `resources/`, `pdfs/`, `screenshots/`, and `diffs/` in this run directory.
