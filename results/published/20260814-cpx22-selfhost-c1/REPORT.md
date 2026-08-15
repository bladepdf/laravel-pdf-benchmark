# Laravel PDF Benchmark Report

Run: `20260814-cpx22-selfhost-c1`  
Profile: `capacity`  
Generated: 2026-08-14T21:57:33+00:00  
Host label: hetzner-cpx22-budget  
Region: Nuremberg, Germany  
Infrastructure: hetzner CPX22; 2 vCPU (shared); 3814 MiB RAM; purpose: budget.  
Load generation: in-process worker pool, co-located with the application on the benchmark host.  
Git: `b6d5d4d8fe01b9088f6af8a91601960f9185aeef`  
Cloudflare plan: not-run; BladePDF plan: not-run; declared BladePDF concurrency: 1.

Percentiles: nearest-rank; application cache and retries disabled.

> Provider-side resource consumption is not observable, so only application-side usage was measured for managed services.

## Core renderer performance

| Renderer | Variant | Template | Scenario / first-observation label | Concurrency | Success | Fail | Timeout | p50 ms | p95 ms | p99 ms | CV % | Peak in-flight | PDF KiB | Successful PDF/s | Observed CPU % | App peak MiB | Render service peak MiB | Queue peak |
|---|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| browsershot | default | modern-invoice | first - fresh browser per render | 1 | 1/1 | 0 | 0 | 470.43 | 470.43 | 470.43 | n/a | 1 | 156.77 | 2.11 | 56.00 | 609.88 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 456.92 | 504.14 | 543.25 | 6.53 | 1 | 156.77 | 2.18 | 1.05 | 614.94 | n/a | n/a |
| browsershot | default | simple-invoice | first - fresh browser per render | 1 | 1/1 | 0 | 0 | 457.30 | 457.30 | 457.30 | n/a | 1 | 61.41 | 2.17 | 50.88 | 577.49 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 446.75 | 489.03 | 505.91 | 6.38 | 1 | 61.41 | 2.24 | 0.90 | 614.21 | n/a | n/a |
| dompdf | default | modern-invoice | first - fresh worker | 1 | 1/1 | 0 | 0 | 129.22 | 129.22 | 129.22 | n/a | 1 | 24.67 | 7.60 | 45.62 | 65.63 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 73.28 | 95.26 | 105.83 | 12.48 | 1 | 24.67 | 13.14 | 48.28 | 75.36 | n/a | n/a |
| dompdf | default | simple-invoice | first - fresh worker | 1 | 1/1 | 0 | 0 | 166.92 | 166.92 | 166.92 | n/a | 1 | 27.82 | 5.81 | 49.41 | 73.83 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 144.49 | 165.17 | 171.40 | 6.45 | 1 | 27.82 | 6.77 | 49.47 | 87.88 | n/a | n/a |
| gotenberg | default | modern-invoice | first - first request after controlled container restart | 1 | 1/1 | 0 | 0 | 245.05 | 245.05 | 245.05 | n/a | 1 | 143.27 | 4.06 | 13.24 | 58.59 | 155.25 | 1.00 |
| gotenberg | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 242.99 | 302.84 | 332.03 | 19.60 | 1 | 143.27 | 3.96 | 76.84 | 102.24 | 361.39 | 1.00 |
| gotenberg | default | simple-invoice | first - first request after controlled container restart | 1 | 1/1 | 0 | 0 | 250.23 | 250.23 | 250.23 | n/a | 1 | 30.64 | 3.97 | 79.89 | 58.38 | 157.72 | 0.00 |
| gotenberg | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 213.19 | 251.02 | 255.69 | 20.32 | 1 | 30.64 | 4.55 | 76.50 | 66.77 | 241.95 | 1.00 |

## Secondary tuning variants

| Renderer | Variant | Template | Scenario / first-observation label | Concurrency | Success | Fail | Timeout | p50 ms | p95 ms | p99 ms | CV % | Peak in-flight | PDF KiB | Successful PDF/s | Observed CPU % | App peak MiB | Render service peak MiB | Queue peak |
|---|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| browsershot-persistent | persistent | modern-invoice | first - first connection to persistent browser | 1 | 1/1 | 0 | 0 | 415.67 | 415.67 | 415.67 | n/a | 1 | 158.35 | 2.38 | 60.79 | 133.50 | 221.14 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 395.77 | 452.17 | 471.64 | 7.27 | 1 | 158.35 | 2.50 | 35.61 | 145.46 | 397.42 | n/a |
| browsershot-persistent | persistent | simple-invoice | first - first connection to persistent browser | 1 | 1/1 | 0 | 0 | 414.57 | 414.57 | 414.57 | n/a | 1 | 61.51 | 2.41 | 50.11 | 133.74 | 185.81 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 384.66 | 433.83 | 454.59 | 6.18 | 1 | 61.51 | 2.55 | 35.20 | 141.67 | 397.99 | n/a |

## Capacity sweep observations

These are observed limits, not a CPU-to-concurrency assumption. Throughput peak and first failure level are descriptive; the report does not assign an automatic production-safe concurrency.

| Renderer | Variant | Template | Tested levels | Declared plan limit | Highest failure-free within limit | Throughput peak at | First failure/timeout at |
|---|---|---|---|---:|---:|---:|---:|
| browsershot | default | modern-invoice | 1 | n/a | 1 | 1 | n/a |
| browsershot | default | simple-invoice | 1 | n/a | 1 | 1 | n/a |
| browsershot-persistent | persistent | modern-invoice | 1 | n/a | 1 | 1 | n/a |
| browsershot-persistent | persistent | simple-invoice | 1 | n/a | 1 | 1 | n/a |
| dompdf | default | modern-invoice | 1 | n/a | 1 | 1 | n/a |
| dompdf | default | simple-invoice | 1 | n/a | 1 | 1 | n/a |
| gotenberg | default | modern-invoice | 1 | n/a | 1 | 1 | n/a |
| gotenberg | default | simple-invoice | 1 | n/a | 1 | 1 | n/a |


## Operational methodology

See `operations.json` for the dated, source-linked installation, dependency, asset, and ownership declarations captured with this run.

## Artifacts

See `summary.json`, `summary.csv`, `raw/`, `resources/`, `pdfs/`, `screenshots/`, and `diffs/` in this run directory.
