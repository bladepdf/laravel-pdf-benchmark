# Laravel PDF Benchmark Report

Run: `20260814-cpx22-bladepdf-starter-c1`  
Profile: `capacity`  
Generated: 2026-08-14T22:01:07+00:00  
Host label: hetzner-cpx22-budget  
Region: Nuremberg, Germany  
Infrastructure: hetzner CPX22; 2 vCPU (shared); 3814 MiB RAM; purpose: budget.  
Load generation: in-process worker pool, co-located with the application on the benchmark host.  
Git: `b6d5d4d8fe01b9088f6af8a91601960f9185aeef`  
Cloudflare plan: not-run; BladePDF plan: starter; declared BladePDF concurrency: 1.

Percentiles: nearest-rank; application cache and retries disabled.

> Provider-side resource consumption is not observable, so only application-side usage was measured for managed services.

## Core renderer performance

| Renderer | Variant | Template | Scenario / first-observation label | Concurrency | Success | Fail | Timeout | p50 ms | p95 ms | p99 ms | CV % | Peak in-flight | PDF KiB | Successful PDF/s | Observed CPU % | App peak MiB | Render service peak MiB | Queue peak |
|---|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| bladepdf | default | modern-invoice | first - first observed API request | 1 | 1/1 | 0 | 0 | 636.70 | 636.70 | 636.70 | n/a | 1 | 156.77 | 1.56 | 5.48 | 61.34 | n/a | n/a |
| bladepdf | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 477.25 | 593.80 | 638.69 | 11.70 | 1 | 156.77 | 2.05 | 5.12 | 103.59 | n/a | n/a |
| bladepdf | default | simple-invoice | first - first observed API request | 1 | 1/1 | 0 | 0 | 462.64 | 462.64 | 462.64 | n/a | 1 | 62.30 | 2.15 | 9.66 | 60.68 | n/a | n/a |
| bladepdf | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 332.57 | 423.29 | 623.70 | 18.01 | 1 | 62.30 | 2.94 | 6.53 | 72.92 | n/a | n/a |

## Capacity sweep observations

These are observed limits, not a CPU-to-concurrency assumption. Throughput peak and first failure level are descriptive; the report does not assign an automatic production-safe concurrency.

| Renderer | Variant | Template | Tested levels | Declared plan limit | Highest failure-free within limit | Throughput peak at | First failure/timeout at |
|---|---|---|---|---:|---:|---:|---:|
| bladepdf | default | modern-invoice | 1 | 1 | 1 | 1 | n/a |
| bladepdf | default | simple-invoice | 1 | 1 | 1 | 1 | n/a |


## Operational methodology

See `operations.json` for the dated, source-linked installation, dependency, asset, and ownership declarations captured with this run.

## Artifacts

See `summary.json`, `summary.csv`, `raw/`, `resources/`, `pdfs/`, `screenshots/`, and `diffs/` in this run directory.
