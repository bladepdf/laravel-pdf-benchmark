# Laravel PDF Benchmark Report

Run: `20260814-ccx33-selfhost-control`  
Profile: `capacity`  
Generated: 2026-08-15T00:39:17+00:00  
Host label: hetzner-ccx33-control  
Region: Nuremberg, Germany  
Infrastructure: hetzner CCX33; 8 vCPU (dedicated); 31335 MiB RAM; purpose: control.  
Load generation: in-process worker pool, co-located with the application on the benchmark host.  
Git: `b6d5d4d8fe01b9088f6af8a91601960f9185aeef`  
Cloudflare plan: not-run; BladePDF plan: not-run; declared BladePDF concurrency: 1.

Percentiles: nearest-rank; application cache and retries disabled.

> Provider-side resource consumption is not observable, so only application-side usage was measured for managed services.

## Core renderer performance

| Renderer | Variant | Template | Scenario / first-observation label | Concurrency | Success | Fail | Timeout | p50 ms | p95 ms | p99 ms | CV % | Peak in-flight | PDF KiB | Successful PDF/s | Observed CPU % | App peak MiB | Render service peak MiB | Queue peak |
|---|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| browsershot | default | modern-invoice | first - fresh browser per render | 1 | 1/1 | 0 | 0 | 302.33 | 302.33 | 302.33 | n/a | 1 | 156.77 | 3.25 | 19.11 | 545.40 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 296.49 | 307.33 | 317.22 | 2.11 | 1 | 156.77 | 3.35 | 0.35 | 619.67 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 326.82 | 338.42 | 345.63 | 2.29 | 2 | 156.77 | 6.09 | 1.05 | 1234.68 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 460.42 | 505.47 | 524.38 | 4.64 | 4 | 156.77 | 8.59 | 2.74 | 2461.39 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 820.74 | 869.23 | 894.12 | 9.32 | 8 | 156.77 | 9.65 | 6.03 | 4879.98 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 1241.66 | 1477.61 | 1574.60 | 16.82 | 12 | 156.77 | 9.32 | 7.79 | 7031.71 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 1691.53 | 2047.41 | 2328.31 | 20.76 | 16 | 156.77 | 9.13 | 10.58 | 9302.31 | n/a | n/a |
| browsershot | default | simple-invoice | first - fresh browser per render | 1 | 1/1 | 0 | 0 | 304.46 | 304.46 | 304.46 | n/a | 1 | 61.41 | 3.23 | 18.17 | 532.30 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 284.98 | 294.45 | 296.32 | 1.88 | 1 | 61.41 | 3.50 | 0.30 | 619.40 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 324.41 | 338.05 | 340.80 | 2.43 | 2 | 61.41 | 6.14 | 0.82 | 1231.55 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 439.08 | 460.71 | 472.11 | 3.37 | 4 | 61.41 | 9.09 | 2.65 | 2430.78 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 807.16 | 883.62 | 925.40 | 10.42 | 8 | 61.41 | 9.83 | 5.49 | 4764.77 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 1282.44 | 1476.32 | 1602.51 | 17.37 | 12 | 61.41 | 9.19 | 7.37 | 6458.75 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 1651.31 | 1970.01 | 2067.87 | 20.68 | 16 | 61.41 | 9.47 | 8.89 | 7612.32 | n/a | n/a |
| dompdf | default | modern-invoice | first - fresh worker | 1 | 1/1 | 0 | 0 | 83.94 | 83.94 | 83.94 | n/a | 1 | 24.67 | 11.84 | 8.88 | 65.09 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 60.85 | 66.59 | 68.50 | 5.22 | 1 | 24.67 | 16.06 | 12.53 | 75.41 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 60.94 | 67.79 | 84.55 | 6.63 | 2 | 24.67 | 31.80 | 24.76 | 149.88 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 62.86 | 70.45 | 88.28 | 8.73 | 4 | 24.67 | 61.25 | 48.47 | 298.54 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 100.40 | 133.24 | 134.83 | 12.05 | 8 | 24.67 | 73.41 | 88.83 | 594.72 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 144.80 | 272.17 | 304.30 | 36.52 | 12 | 24.67 | 69.75 | 70.97 | 872.11 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 205.61 | 425.28 | 466.60 | 39.70 | 16 | 24.67 | 65.78 | 68.66 | 1138.12 | n/a | n/a |
| dompdf | default | simple-invoice | first - fresh worker | 1 | 1/1 | 0 | 0 | 133.31 | 133.31 | 133.31 | n/a | 1 | 27.82 | 7.47 | 10.27 | 75.15 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 108.79 | 116.53 | 122.30 | 4.06 | 1 | 27.82 | 9.09 | 12.49 | 87.60 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 112.77 | 123.09 | 136.83 | 4.61 | 2 | 27.82 | 17.56 | 24.76 | 172.99 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 113.59 | 123.51 | 155.27 | 7.68 | 4 | 27.82 | 34.45 | 49.52 | 352.58 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 169.40 | 206.19 | 211.08 | 8.53 | 8 | 27.82 | 44.48 | 89.23 | 697.96 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 236.02 | 352.90 | 455.05 | 29.43 | 12 | 27.82 | 43.15 | 78.43 | 1024.24 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 354.59 | 481.50 | 630.20 | 28.04 | 16 | 27.82 | 41.96 | 79.56 | 1367.22 | n/a | n/a |
| gotenberg | default | modern-invoice | first - first request after controlled container restart | 1 | 1/1 | 0 | 0 | 160.06 | 160.06 | 160.06 | n/a | 1 | 143.27 | 6.15 | 1.91 | 59.05 | 149.96 | 1.00 |
| gotenberg | default | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 139.26 | 146.88 | 153.23 | 16.99 | 1 | 143.27 | 7.03 | 31.39 | 101.50 | 231.23 | 1.00 |
| gotenberg | default | modern-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 173.55 | 189.03 | 229.25 | 19.04 | 2 | 143.27 | 11.02 | 48.74 | 158.51 | 399.70 | 2.00 |
| gotenberg | default | modern-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 254.43 | 297.80 | 325.81 | 9.59 | 4 | 143.27 | 15.48 | 64.71 | 276.38 | 309.90 | 4.00 |
| gotenberg | default | modern-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 489.67 | 564.36 | 682.69 | 15.05 | 8 | 143.27 | 15.23 | 64.49 | 509.06 | 376.36 | 8.00 |
| gotenberg | default | modern-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 743.56 | 853.90 | 1320.30 | 21.68 | 12 | 143.27 | 15.10 | 63.47 | 747.74 | 466.84 | 12.00 |
| gotenberg | default | modern-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 1028.99 | 1274.86 | 1565.98 | 22.61 | 16 | 143.27 | 14.58 | 62.09 | 983.27 | 539.56 | 16.00 |
| gotenberg | default | simple-invoice | first - first request after controlled container restart | 1 | 1/1 | 0 | 0 | 143.26 | 143.26 | 143.26 | n/a | 1 | 30.64 | 6.92 | 2.13 | 58.28 | 151.30 | 1.00 |
| gotenberg | default | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 118.97 | 125.40 | 139.11 | 19.16 | 1 | 30.64 | 8.18 | 32.83 | 67.05 | 364.27 | 1.00 |
| gotenberg | default | simple-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 150.10 | 161.45 | 168.80 | 5.10 | 2 | 30.64 | 13.16 | 52.81 | 125.25 | 261.94 | 2.00 |
| gotenberg | default | simple-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 239.70 | 266.12 | 283.97 | 7.91 | 4 | 30.64 | 16.51 | 61.32 | 240.95 | 320.26 | 4.00 |
| gotenberg | default | simple-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 432.12 | 657.74 | 783.82 | 25.37 | 8 | 30.64 | 16.21 | 60.03 | 475.05 | 539.50 | 8.00 |
| gotenberg | default | simple-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 707.33 | 752.88 | 786.86 | 15.84 | 12 | 30.64 | 16.80 | 61.29 | 707.89 | 382.09 | 12.00 |
| gotenberg | default | simple-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 936.95 | 1015.23 | 1075.27 | 19.00 | 16 | 30.64 | 16.27 | 62.14 | 940.98 | 386.43 | 16.00 |

## Secondary tuning variants

| Renderer | Variant | Template | Scenario / first-observation label | Concurrency | Success | Fail | Timeout | p50 ms | p95 ms | p99 ms | CV % | Peak in-flight | PDF KiB | Successful PDF/s | Observed CPU % | App peak MiB | Render service peak MiB | Queue peak |
|---|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| browsershot-persistent | persistent | modern-invoice | first - first connection to persistent browser | 1 | 1/1 | 0 | 0 | 245.44 | 245.44 | 245.44 | n/a | 1 | 158.35 | 4.05 | 22.67 | 134.65 | 271.18 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 249.34 | 258.38 | 261.15 | 2.03 | 1 | 158.35 | 4.00 | 13.02 | 139.14 | 458.36 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 283.08 | 298.56 | 301.52 | 2.79 | 2 | 158.35 | 7.02 | 22.27 | 277.23 | 484.55 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 393.35 | 416.58 | 431.51 | 4.89 | 4 | 158.35 | 10.04 | 32.95 | 555.20 | 496.87 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 691.68 | 788.55 | 808.82 | 13.06 | 8 | 158.35 | 11.30 | 38.98 | 1103.63 | 481.50 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 1044.94 | 1225.56 | 1265.20 | 17.79 | 12 | 158.35 | 11.30 | 41.03 | 1645.15 | 577.14 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 1430.35 | 1723.12 | 1921.79 | 21.50 | 16 | 158.35 | 11.04 | 43.24 | 2184.75 | 632.24 | n/a |
| browsershot-persistent | persistent | simple-invoice | first - first connection to persistent browser | 1 | 1/1 | 0 | 0 | 240.77 | 240.77 | 240.77 | n/a | 1 | 61.51 | 4.12 | 23.14 | 134.84 | 241.04 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-1 | 1 | 100/100 | 0 | 0 | 236.33 | 246.98 | 253.45 | 2.22 | 1 | 61.51 | 4.21 | 12.96 | 141.13 | 454.07 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-2 | 2 | 100/100 | 0 | 0 | 269.48 | 283.69 | 295.27 | 2.90 | 2 | 61.51 | 7.35 | 21.52 | 276.58 | 433.57 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-4 | 4 | 100/100 | 0 | 0 | 382.93 | 408.04 | 435.42 | 4.85 | 4 | 61.51 | 10.36 | 31.48 | 544.28 | 440.86 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-8 | 8 | 100/100 | 0 | 0 | 678.91 | 763.51 | 776.21 | 14.52 | 8 | 61.51 | 11.54 | 37.80 | 1097.01 | 485.02 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-12 | 12 | 100/100 | 0 | 0 | 1005.04 | 1236.14 | 1268.55 | 18.34 | 12 | 61.51 | 11.65 | 40.35 | 1627.55 | 546.79 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-16 | 16 | 100/100 | 0 | 0 | 1408.78 | 1744.15 | 1876.91 | 23.37 | 16 | 61.51 | 11.25 | 42.27 | 2182.96 | 595.24 | n/a |

## Capacity sweep observations

These are observed limits, not a CPU-to-concurrency assumption. Throughput peak and first failure level are descriptive; the report does not assign an automatic production-safe concurrency.

| Renderer | Variant | Template | Tested levels | Declared plan limit | Highest failure-free within limit | Throughput peak at | First failure/timeout at |
|---|---|---|---|---:|---:|---:|---:|
| browsershot | default | modern-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 8 | n/a |
| browsershot | default | simple-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 8 | n/a |
| browsershot-persistent | persistent | modern-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 12 | n/a |
| browsershot-persistent | persistent | simple-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 12 | n/a |
| dompdf | default | modern-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 8 | n/a |
| dompdf | default | simple-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 8 | n/a |
| gotenberg | default | modern-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 4 | n/a |
| gotenberg | default | simple-invoice | 1, 2, 4, 8, 12, 16 | n/a | 16 | 12 | n/a |


## Operational methodology

See `operations.json` for the dated, source-linked installation, dependency, asset, and ownership declarations captured with this run.

## Artifacts

See `summary.json`, `summary.csv`, `raw/`, `resources/`, `pdfs/`, `screenshots/`, and `diffs/` in this run directory.
