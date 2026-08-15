# Laravel PDF Benchmark Report

Run: `20260814-cpx42-bladepdf-scale-capacity`  
Profile: `capacity`  
Generated: 2026-08-15T00:13:46+00:00  
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
| bladepdf | default | modern-invoice | first - first observed API request | 1 | 1/1 | 0 | 0 | 603.95 | 603.95 | 603.95 | n/a | 1 | 156.77 | 1.64 | 1.03 | 61.28 | n/a | n/a |
| bladepdf | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 470.15 | 584.85 | 631.51 | 11.65 | 1 | 156.77 | 2.09 | 1.38 | 103.45 | n/a | n/a |
| bladepdf | default | modern-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 480.46 | 583.82 | 698.07 | 12.01 | 2 | 156.77 | 4.03 | 2.64 | 165.41 | n/a | n/a |
| bladepdf | default | modern-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 488.80 | 568.18 | 619.14 | 8.85 | 4 | 156.77 | 7.86 | 5.17 | 290.03 | n/a | n/a |
| bladepdf | default | modern-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 565.89 | 704.63 | 756.68 | 10.93 | 8 | 156.77 | 13.11 | 7.93 | 536.15 | n/a | n/a |
| bladepdf | default | simple-invoice | first - first observed API request | 1 | 1/1 | 0 | 0 | 482.41 | 482.41 | 482.41 | n/a | 1 | 62.30 | 2.07 | 1.81 | 60.68 | n/a | n/a |
| bladepdf | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 332.26 | 410.04 | 449.28 | 12.94 | 1 | 62.30 | 2.90 | 1.84 | 72.88 | n/a | n/a |
| bladepdf | default | simple-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 329.43 | 406.09 | 418.76 | 12.61 | 2 | 62.30 | 5.89 | 3.61 | 134.05 | n/a | n/a |
| bladepdf | default | simple-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 347.61 | 423.03 | 460.79 | 13.21 | 4 | 62.30 | 11.20 | 6.97 | 256.54 | n/a | n/a |
| bladepdf | default | simple-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 396.28 | 510.67 | 558.36 | 11.90 | 8 | 62.30 | 18.93 | 10.43 | 501.30 | n/a | n/a |

## Capacity sweep observations

These are observed limits, not a CPU-to-concurrency assumption. Throughput peak and first failure level are descriptive; the report does not assign an automatic production-safe concurrency.

| Renderer | Variant | Template | Tested levels | Declared plan limit | Highest failure-free within limit | Throughput peak at | First failure/timeout at |
|---|---|---|---|---:|---:|---:|---:|
| bladepdf | default | modern-invoice | 1, 2, 4, 8 | 8 | 8 | 8 | n/a |
| bladepdf | default | simple-invoice | 1, 2, 4, 8 | 8 | 8 | 8 | n/a |


## Operational methodology

See `operations.json` for the dated, source-linked installation, dependency, asset, and ownership declarations captured with this run.

## Artifacts

See `summary.json`, `summary.csv`, `raw/`, `resources/`, `pdfs/`, `screenshots/`, and `diffs/` in this run directory.
