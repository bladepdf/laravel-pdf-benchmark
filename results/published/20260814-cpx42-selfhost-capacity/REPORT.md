# Laravel PDF Benchmark Report

Run: `20260814-cpx42-selfhost-capacity`  
Profile: `capacity`  
Generated: 2026-08-15T00:08:20+00:00  
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
| browsershot | default | modern-invoice | first - fresh browser per render | 1 | 1/1 | 0 | 0 | 443.64 | 443.64 | 443.64 | n/a | 1 | 156.77 | 2.23 | 17.85 | 611.06 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 466.85 | 511.29 | 516.91 | 6.95 | 1 | 156.77 | 2.15 | 0.46 | 621.16 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 382.82 | 419.40 | 425.12 | 4.86 | 2 | 156.77 | 5.20 | 1.05 | 1235.93 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 428.55 | 460.35 | 462.78 | 4.01 | 4 | 156.77 | 9.24 | 2.62 | 2436.82 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 683.64 | 753.70 | 765.93 | 9.36 | 8 | 156.77 | 11.48 | 5.62 | 4907.15 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 1019.35 | 1206.36 | 1239.09 | 15.15 | 12 | 156.77 | 11.55 | 7.73 | 6702.74 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 1324.52 | 1716.37 | 1796.27 | 19.01 | 16 | 156.77 | 11.34 | 9.60 | 8053.43 | n/a | n/a |
| browsershot | default | simple-invoice | first - fresh browser per render | 1 | 1/1 | 0 | 0 | 441.06 | 441.06 | 441.06 | n/a | 1 | 61.41 | 2.25 | 21.05 | 588.36 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 457.65 | 496.70 | 522.74 | 6.23 | 1 | 61.41 | 2.19 | 0.35 | 623.15 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 360.99 | 390.40 | 433.09 | 4.99 | 2 | 61.41 | 5.50 | 0.94 | 1233.34 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 414.94 | 445.41 | 454.30 | 3.79 | 4 | 61.41 | 9.60 | 2.60 | 2471.23 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 668.80 | 716.97 | 720.21 | 8.40 | 8 | 61.41 | 11.72 | 5.76 | 4750.59 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 991.95 | 1185.49 | 1245.15 | 14.09 | 12 | 61.41 | 11.44 | 7.12 | 6615.52 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 1296.02 | 1523.46 | 1678.02 | 18.07 | 16 | 61.41 | 11.75 | 10.03 | 8937.66 | n/a | n/a |
| dompdf | default | modern-invoice | first - fresh worker | 1 | 1/1 | 0 | 0 | 100.38 | 100.38 | 100.38 | n/a | 1 | 24.67 | 9.75 | 7.31 | 65.05 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 67.51 | 74.69 | 76.88 | 6.38 | 1 | 24.67 | 14.50 | 12.50 | 75.39 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 66.21 | 75.87 | 117.89 | 11.55 | 2 | 24.67 | 28.97 | 24.48 | 150.66 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 65.40 | 76.76 | 98.83 | 10.30 | 4 | 24.67 | 57.96 | 48.25 | 301.77 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 68.50 | 103.16 | 119.01 | 17.17 | 8 | 24.67 | 100.99 | 85.71 | 593.30 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 76.39 | 206.63 | 212.55 | 42.84 | 12 | 24.67 | 95.31 | 57.54 | 852.11 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 143.59 | 263.99 | 305.67 | 29.45 | 16 | 24.67 | 97.94 | 79.09 | 1156.00 | n/a | n/a |
| dompdf | default | simple-invoice | first - fresh worker | 1 | 1/1 | 0 | 0 | 176.04 | 176.04 | 176.04 | n/a | 1 | 27.82 | 5.58 | 12.55 | 70.66 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 137.13 | 147.19 | 156.68 | 3.81 | 1 | 27.82 | 7.23 | 12.48 | 87.48 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 137.19 | 150.73 | 163.41 | 4.74 | 2 | 27.82 | 14.11 | 24.14 | 173.93 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 137.75 | 154.34 | 168.57 | 5.86 | 4 | 27.82 | 28.14 | 48.12 | 348.21 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 140.45 | 169.22 | 180.46 | 8.05 | 8 | 27.82 | 52.20 | 86.26 | 694.96 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 147.11 | 352.03 | 491.34 | 43.58 | 12 | 27.82 | 49.67 | 55.56 | 1029.40 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 290.34 | 370.01 | 390.66 | 14.84 | 16 | 27.82 | 50.61 | 81.87 | 1387.91 | n/a | n/a |
| gotenberg | default | modern-invoice | first - first request after controlled container restart | 1 | 1/1 | 0 | 0 | 272.56 | 272.56 | 272.56 | n/a | 1 | 143.27 | 3.63 | 30.59 | 59.05 | 156.63 | 1.00 |
| gotenberg | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 205.67 | 231.73 | 284.47 | 14.00 | 1 | 143.27 | 4.79 | 33.46 | 101.48 | 228.83 | 1.00 |
| gotenberg | default | modern-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 192.44 | 251.36 | 296.64 | 24.26 | 2 | 143.27 | 9.76 | 46.69 | 159.95 | 403.79 | 2.00 |
| gotenberg | default | modern-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 251.70 | 329.26 | 589.07 | 30.04 | 4 | 143.27 | 14.56 | 59.50 | 275.82 | 450.89 | 4.00 |
| gotenberg | default | modern-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 447.86 | 575.67 | 607.45 | 16.63 | 8 | 143.27 | 15.93 | 62.74 | 509.54 | 406.48 | 8.00 |
| gotenberg | default | modern-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 702.87 | 817.62 | 1081.35 | 21.42 | 12 | 143.27 | 16.04 | 62.90 | 747.25 | 415.77 | 12.00 |
| gotenberg | default | modern-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 966.49 | 1268.77 | 1376.83 | 22.12 | 16 | 143.27 | 15.57 | 61.97 | 982.43 | 528.92 | 16.00 |
| gotenberg | default | simple-invoice | first - first request after controlled container restart | 1 | 1/1 | 0 | 0 | 194.06 | 194.06 | 194.06 | n/a | 1 | 30.64 | 5.08 | 7.78 | 58.20 | 156.16 | 1.00 |
| gotenberg | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 172.47 | 206.61 | 234.53 | 23.85 | 1 | 30.64 | 5.62 | 33.81 | 66.94 | 267.29 | 1.00 |
| gotenberg | default | simple-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 146.81 | 192.12 | 219.60 | 12.46 | 2 | 30.64 | 13.01 | 50.59 | 125.09 | 270.54 | 2.00 |
| gotenberg | default | simple-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 219.53 | 263.33 | 310.88 | 16.78 | 4 | 30.64 | 16.61 | 59.33 | 242.36 | 300.53 | 4.00 |
| gotenberg | default | simple-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 411.33 | 684.07 | 826.68 | 28.86 | 8 | 30.64 | 16.97 | 60.66 | 475.89 | 517.75 | 8.00 |
| gotenberg | default | simple-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 647.75 | 706.82 | 720.85 | 16.97 | 12 | 30.64 | 18.39 | 61.87 | 708.95 | 388.21 | 12.00 |
| gotenberg | default | simple-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 850.56 | 962.82 | 972.75 | 19.45 | 16 | 30.64 | 17.31 | 61.20 | 942.02 | 359.73 | 16.00 |

## Secondary tuning variants

| Renderer | Variant | Template | Scenario / first-observation label | Concurrency | Success | Fail | Timeout | p50 ms | p95 ms | p99 ms | CV % | Peak in-flight | PDF KiB | Successful PDF/s | Observed CPU % | App peak MiB | Render service peak MiB | Queue peak |
|---|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| browsershot-persistent | persistent | modern-invoice | first - first connection to persistent browser | 1 | 1/1 | 0 | 0 | 324.64 | 324.64 | 324.64 | n/a | 1 | 158.35 | 3.07 | 25.23 | 134.20 | 296.29 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 363.40 | 397.01 | 405.47 | 7.18 | 1 | 158.35 | 2.79 | 15.66 | 141.33 | 468.49 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 315.32 | 350.99 | 366.11 | 6.75 | 2 | 158.35 | 6.34 | 22.51 | 279.59 | 502.73 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 338.02 | 365.51 | 386.70 | 5.12 | 4 | 158.35 | 11.61 | 31.40 | 555.31 | 519.97 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 543.17 | 613.43 | 623.07 | 10.78 | 8 | 158.35 | 14.45 | 40.07 | 1098.40 | 517.89 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 813.83 | 985.66 | 1016.05 | 19.17 | 12 | 158.35 | 14.21 | 41.48 | 1645.82 | 608.24 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 1159.88 | 1387.09 | 1417.08 | 20.34 | 16 | 158.35 | 13.46 | 42.27 | 2185.99 | 668.50 | n/a |
| browsershot-persistent | persistent | simple-invoice | first - first connection to persistent browser | 1 | 1/1 | 0 | 0 | 365.84 | 365.84 | 365.84 | n/a | 1 | 61.51 | 2.71 | 27.29 | 136.92 | 263.53 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 346.76 | 388.28 | 417.38 | 7.61 | 1 | 61.51 | 2.89 | 15.56 | 138.20 | 459.38 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 302.36 | 336.86 | 361.70 | 6.15 | 2 | 61.51 | 6.54 | 21.29 | 275.55 | 469.40 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 331.32 | 358.23 | 374.25 | 5.38 | 4 | 61.51 | 11.83 | 31.39 | 550.03 | 487.78 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 527.75 | 603.48 | 614.28 | 12.14 | 8 | 61.51 | 14.71 | 37.86 | 1098.02 | 488.79 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 760.25 | 920.13 | 983.79 | 14.25 | 12 | 61.51 | 14.95 | 41.17 | 1627.31 | 543.15 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 1062.37 | 1367.78 | 1482.37 | 23.77 | 16 | 61.51 | 14.46 | 42.84 | 2182.73 | 612.34 | n/a |

## Capacity sweep observations

These are observed limits, not a CPU-to-concurrency assumption. Throughput peak and first failure level are descriptive; the report does not assign an automatic production-safe concurrency.

| Renderer | Variant | Template | Tested levels | Declared plan limit | Highest failure-free within limit | Throughput peak at | First failure/timeout at |
|---|---|---|---|---:|---:|---:|---:|
| browsershot | default | modern-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 12 | n/a |
| browsershot | default | simple-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 16 | n/a |
| browsershot-persistent | persistent | modern-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 8 | n/a |
| browsershot-persistent | persistent | simple-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 12 | n/a |
| dompdf | default | modern-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 8 | n/a |
| dompdf | default | simple-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 8 | n/a |
| gotenberg | default | modern-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 12 | n/a |
| gotenberg | default | simple-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 12 | n/a |


## Operational methodology

See `operations.json` for the dated, source-linked installation, dependency, asset, and ownership declarations captured with this run.

## Artifacts

See `summary.json`, `summary.csv`, `raw/`, `resources/`, `pdfs/`, `screenshots/`, and `diffs/` in this run directory.
