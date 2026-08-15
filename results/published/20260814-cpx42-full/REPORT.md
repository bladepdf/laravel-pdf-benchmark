# Laravel PDF Benchmark Report

Run: `20260814-cpx42-full`  
Profile: `full`  
Generated: 2026-08-15T14:04:29+00:00  
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
| bladepdf | default | modern-invoice | first - first observed API request | 1 | 1/1 | 0 | 0 | 677.58 | 677.58 | 677.58 | n/a | 1 | 156.77 | 1.47 | 0.73 | 61.46 | n/a | n/a |
| bladepdf | default | modern-invoice | sequential | 1 | 50/50 | 0 | 0 | 458.34 | 525.57 | 593.95 | 8.68 | 1 | 156.77 | 2.14 | 1.27 | 82.66 | n/a | n/a |
| bladepdf | default | modern-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 490.57 | 585.86 | 628.67 | 9.58 | 5 | 156.77 | 9.89 | 6.68 | 352.20 | n/a | n/a |
| bladepdf | default | modern-invoice | concurrency-10 [over declared plan limit 8] | 10 | 100/100 | 0 | 0 | 610.76 | 754.58 | 977.65 | 12.55 | 10 | 156.77 | 15.55 | 10.05 | 659.75 | n/a | n/a |
| bladepdf | default | simple-invoice | first - first observed API request | 1 | 1/1 | 0 | 0 | 463.32 | 463.32 | 463.32 | n/a | 1 | 62.30 | 2.13 | 1.87 | 60.52 | n/a | n/a |
| bladepdf | default | simple-invoice | sequential | 1 | 50/50 | 0 | 0 | 338.13 | 408.92 | 434.87 | 11.91 | 1 | 62.30 | 2.85 | 1.83 | 67.25 | n/a | n/a |
| bladepdf | default | simple-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 353.45 | 461.91 | 480.22 | 14.07 | 5 | 62.30 | 13.75 | 8.22 | 318.89 | n/a | n/a |
| bladepdf | default | simple-invoice | concurrency-10 [over declared plan limit 8] | 10 | 100/100 | 0 | 0 | 406.53 | 491.09 | 685.04 | 14.77 | 10 | 62.30 | 23.35 | 14.01 | 623.68 | n/a | n/a |
| browsershot | default | modern-invoice | first - fresh browser per render | 1 | 1/1 | 0 | 0 | 451.30 | 451.30 | 451.30 | n/a | 1 | 156.77 | 2.20 | 17.30 | 612.09 | n/a | n/a |
| browsershot | default | modern-invoice | sequential | 1 | 50/50 | 0 | 0 | 477.56 | 521.38 | 536.59 | 7.16 | 1 | 156.77 | 2.12 | 0.71 | 622.78 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 492.57 | 526.05 | 539.99 | 3.98 | 5 | 156.77 | 10.09 | 3.57 | 3059.54 | n/a | n/a |
| browsershot | default | modern-invoice | concurrency-10 | 10 | 100/100 | 0 | 0 | 851.77 | 953.03 | 1021.73 | 7.43 | 10 | 156.77 | 11.47 | 6.51 | 5672.52 | n/a | n/a |
| browsershot | default | simple-invoice | first - fresh browser per render | 1 | 1/1 | 0 | 0 | 397.15 | 397.15 | 397.15 | n/a | 1 | 61.41 | 2.49 | 19.94 | 611.98 | n/a | n/a |
| browsershot | default | simple-invoice | sequential | 1 | 50/50 | 0 | 0 | 459.60 | 507.76 | 513.75 | 6.54 | 1 | 61.41 | 2.17 | 0.59 | 622.28 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 471.14 | 509.90 | 534.37 | 4.40 | 5 | 61.41 | 10.53 | 3.42 | 3076.48 | n/a | n/a |
| browsershot | default | simple-invoice | concurrency-10 | 10 | 100/100 | 0 | 0 | 826.49 | 936.61 | 975.08 | 11.18 | 10 | 61.41 | 11.81 | 6.78 | 5936.55 | n/a | n/a |
| cloudflare | default | modern-invoice | first - first observed API request | 1 | 1/1 | 0 | 0 | 486.99 | 486.99 | 486.99 | n/a | 1 | 201.97 | 2.04 | 1.53 | 61.21 | n/a | n/a |
| cloudflare | default | modern-invoice | sequential | 1 | 50/50 | 0 | 0 | 521.56 | 785.92 | 1118.43 | 24.81 | 1 | 201.97 | 1.79 | 1.22 | 84.52 | n/a | n/a |
| cloudflare | default | modern-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 471.86 | 782.25 | 853.53 | 263.22 | 5 | 201.97 | 3.52 | 1.78 | 342.63 | n/a | n/a |
| cloudflare | default | modern-invoice | concurrency-10 | 10 | 41/100 | 59 | 0 | 486.39 | 787.09 | 1032.92 | 29.82 | 10 | 201.97 | 8.47 | 9.81 | 641.30 | n/a | n/a |
| cloudflare | default | simple-invoice | first - first observed API request | 1 | 1/1 | 0 | 0 | 583.37 | 583.37 | 583.37 | n/a | 1 | 77.30 | 1.70 | 0.85 | 60.39 | n/a | n/a |
| cloudflare | default | simple-invoice | sequential | 1 | 50/50 | 0 | 0 | 365.46 | 506.04 | 713.52 | 19.15 | 1 | 77.30 | 2.60 | 1.66 | 67.91 | n/a | n/a |
| cloudflare | default | simple-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 381.90 | 630.33 | 1674.08 | 117.82 | 5 | 77.30 | 7.41 | 3.65 | 316.38 | n/a | n/a |
| cloudflare | default | simple-invoice | concurrency-10 | 10 | 35/100 | 65 | 0 | 386.46 | 708.84 | 888.62 | 32.99 | 10 | 77.30 | 9.87 | 10.26 | 614.59 | n/a | n/a |
| dompdf | default | modern-invoice | first - fresh worker | 1 | 1/1 | 0 | 0 | 94.27 | 94.27 | 94.27 | n/a | 1 | 24.67 | 10.29 | 7.72 | 65.04 | n/a | n/a |
| dompdf | default | modern-invoice | sequential | 1 | 50/50 | 0 | 0 | 67.50 | 77.24 | 100.78 | 8.57 | 1 | 24.67 | 14.36 | 12.53 | 75.34 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 66.11 | 83.07 | 101.64 | 11.60 | 5 | 24.67 | 70.79 | 60.44 | 375.80 | n/a | n/a |
| dompdf | default | modern-invoice | concurrency-10 | 10 | 100/100 | 0 | 0 | 68.73 | 146.74 | 200.39 | 40.15 | 10 | 24.67 | 90.94 | 61.73 | 723.76 | n/a | n/a |
| dompdf | default | simple-invoice | first - fresh worker | 1 | 1/1 | 0 | 0 | 172.23 | 172.23 | 172.23 | n/a | 1 | 27.82 | 5.76 | 12.95 | 74.58 | n/a | n/a |
| dompdf | default | simple-invoice | sequential | 1 | 50/50 | 0 | 0 | 138.31 | 148.83 | 163.38 | 3.70 | 1 | 27.82 | 7.17 | 12.46 | 87.69 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 135.94 | 159.43 | 174.22 | 6.79 | 5 | 27.82 | 35.81 | 60.57 | 436.78 | n/a | n/a |
| dompdf | default | simple-invoice | concurrency-10 | 10 | 100/100 | 0 | 0 | 143.71 | 287.83 | 524.85 | 43.53 | 10 | 27.82 | 43.53 | 59.85 | 863.31 | n/a | n/a |
| gotenberg | default | modern-invoice | first - first request after controlled container restart | 1 | 1/1 | 0 | 0 | 285.68 | 285.68 | 285.68 | n/a | 1 | 143.27 | 3.48 | 31.50 | 58.78 | 157.25 | 1.00 |
| gotenberg | default | modern-invoice | sequential | 1 | 50/50 | 0 | 0 | 199.41 | 236.68 | 278.65 | 11.31 | 1 | 143.27 | 4.93 | 32.81 | 80.23 | 219.68 | 1.00 |
| gotenberg | default | modern-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 299.41 | 417.98 | 764.04 | 32.19 | 5 | 143.27 | 15.22 | 60.53 | 336.23 | 463.74 | 5.00 |
| gotenberg | default | modern-invoice | concurrency-10 | 10 | 100/100 | 0 | 0 | 614.95 | 855.85 | 1142.77 | 26.37 | 10 | 143.27 | 15.67 | 61.32 | 630.04 | 550.91 | 10.00 |
| gotenberg | default | simple-invoice | first - first request after controlled container restart | 1 | 1/1 | 0 | 0 | 213.27 | 213.27 | 213.27 | n/a | 1 | 30.64 | 4.66 | 6.00 | 58.11 | 155.24 | 1.00 |
| gotenberg | default | simple-invoice | sequential | 1 | 50/50 | 0 | 0 | 173.26 | 201.41 | 242.20 | 9.94 | 1 | 30.64 | 5.62 | 33.41 | 62.64 | 364.85 | 1.00 |
| gotenberg | default | simple-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 269.16 | 376.59 | 609.04 | 25.37 | 5 | 30.64 | 17.24 | 58.87 | 301.15 | 347.91 | 5.00 |
| gotenberg | default | simple-invoice | concurrency-10 | 10 | 100/100 | 0 | 0 | 561.43 | 794.88 | 1267.17 | 31.98 | 10 | 30.64 | 16.82 | 60.31 | 592.66 | 520.57 | 10.00 |

## Secondary tuning variants

| Renderer | Variant | Template | Scenario / first-observation label | Concurrency | Success | Fail | Timeout | p50 ms | p95 ms | p99 ms | CV % | Peak in-flight | PDF KiB | Successful PDF/s | Observed CPU % | App peak MiB | Render service peak MiB | Queue peak |
|---|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| browsershot-persistent | persistent | modern-invoice | first - first connection to persistent browser | 1 | 1/1 | 0 | 0 | 393.01 | 393.01 | 393.01 | n/a | 1 | 158.35 | 2.51 | 26.51 | 135.71 | 251.54 | n/a |
| browsershot-persistent | persistent | modern-invoice | sequential | 1 | 50/50 | 0 | 0 | 358.29 | 394.04 | 401.48 | 5.53 | 1 | 158.35 | 2.76 | 15.70 | 140.68 | 404.95 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 384.96 | 418.29 | 459.37 | 7.37 | 5 | 158.35 | 12.68 | 35.64 | 692.83 | 467.03 | n/a |
| browsershot-persistent | persistent | modern-invoice | concurrency-10 | 10 | 100/100 | 0 | 0 | 666.99 | 792.92 | 841.37 | 14.01 | 10 | 158.35 | 14.32 | 40.52 | 1362.11 | 476.11 | n/a |
| browsershot-persistent | persistent | simple-invoice | first - first connection to persistent browser | 1 | 1/1 | 0 | 0 | 384.71 | 384.71 | 384.71 | n/a | 1 | 61.51 | 2.56 | 24.80 | 133.18 | 219.84 | n/a |
| browsershot-persistent | persistent | simple-invoice | sequential | 1 | 50/50 | 0 | 0 | 343.94 | 372.52 | 392.73 | 5.88 | 1 | 61.51 | 2.90 | 15.89 | 139.31 | 427.88 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-5 | 5 | 100/100 | 0 | 0 | 371.29 | 405.91 | 438.91 | 6.85 | 5 | 61.51 | 13.21 | 34.32 | 689.10 | 449.59 | n/a |
| browsershot-persistent | persistent | simple-invoice | concurrency-10 | 10 | 100/100 | 0 | 0 | 649.48 | 777.07 | 818.78 | 13.55 | 10 | 61.51 | 15.05 | 40.34 | 1369.04 | 488.53 | n/a |


## Reviewed fidelity features

| Renderer | Template | Mode | Feature | Result | Problem |
|---|---|---|---|---|---|
| browsershot | simple-invoice | portable | Table layout | Pass | n/a |
| browsershot | simple-invoice | portable | Two-page manual break | Pass | n/a |
| browsershot | simple-invoice | portable | Portable PNG logo | Pass | n/a |
| dompdf | simple-invoice | portable | Table layout | Pass | n/a |
| dompdf | simple-invoice | portable | Two-page manual break | Pass | n/a |
| dompdf | simple-invoice | portable | Portable PNG logo | Pass | n/a |
| gotenberg | simple-invoice | portable | Table layout | Pass | n/a |
| gotenberg | simple-invoice | portable | Two-page manual break | Pass | n/a |
| gotenberg | simple-invoice | portable | Portable PNG logo | Pass | n/a |
| cloudflare | simple-invoice | portable | Table layout | Pass | n/a |
| cloudflare | simple-invoice | portable | Two-page manual break | Pass | n/a |
| cloudflare | simple-invoice | portable | Portable PNG logo | Pass | n/a |
| bladepdf | simple-invoice | portable | Table layout | Pass | n/a |
| bladepdf | simple-invoice | portable | Two-page manual break | Pass | n/a |
| bladepdf | simple-invoice | portable | Portable PNG logo | Pass | n/a |
| browsershot | modern-invoice | portable | Gradient and Flexbox | Pass | n/a |
| browsershot | modern-invoice | portable | CSS Grid | Pass | n/a |
| browsershot | modern-invoice | portable | Inline SVG | Pass | n/a |
| browsershot | modern-invoice | portable | Inter WOFF2 | Pass | n/a |
| dompdf | modern-invoice | portable | Gradient and Flexbox | Fail | Missing the entire top card and SVG images; layout differs from the reference |
| dompdf | modern-invoice | portable | CSS Grid | Fail | Missing the entire top card and SVG images; layout differs from the reference |
| dompdf | modern-invoice | portable | Inline SVG | Fail | Missing the entire top card and SVG images; layout differs from the reference |
| dompdf | modern-invoice | portable | Inter WOFF2 | Fail | Missing the entire top card and SVG images; layout differs from the reference |
| gotenberg | modern-invoice | portable | Gradient and Flexbox | Pass | n/a |
| gotenberg | modern-invoice | portable | CSS Grid | Pass | n/a |
| gotenberg | modern-invoice | portable | Inline SVG | Pass | n/a |
| gotenberg | modern-invoice | portable | Inter WOFF2 | Pass | n/a |
| cloudflare | modern-invoice | portable | Gradient and Flexbox | Pass | n/a |
| cloudflare | modern-invoice | portable | CSS Grid | Pass | n/a |
| cloudflare | modern-invoice | portable | Inline SVG | Pass | n/a |
| cloudflare | modern-invoice | portable | Inter WOFF2 | Pass | n/a |
| bladepdf | modern-invoice | portable | Gradient and Flexbox | Pass | n/a |
| bladepdf | modern-invoice | portable | CSS Grid | Pass | n/a |
| bladepdf | modern-invoice | portable | Inline SVG | Pass | n/a |
| bladepdf | modern-invoice | portable | Inter WOFF2 | Pass | n/a |
| browsershot | long-report | portable | Ten-page chapter pagination | Pass | n/a |
| browsershot | long-report | portable | Long table and repeated header | Pass | n/a |
| browsershot | long-report | portable | Deterministic chart | Pass | n/a |
| dompdf | long-report | portable | Ten-page chapter pagination | Fail | Different layout (text spacing/alignment, etc.) and missing columns |
| dompdf | long-report | portable | Long table and repeated header | Fail | Different layout (text spacing/alignment, etc.) and missing columns |
| dompdf | long-report | portable | Deterministic chart | Fail | Different layout (text spacing/alignment, etc.) and missing columns |
| gotenberg | long-report | portable | Ten-page chapter pagination | Pass | n/a |
| gotenberg | long-report | portable | Long table and repeated header | Pass | n/a |
| gotenberg | long-report | portable | Deterministic chart | Pass | n/a |
| cloudflare | long-report | portable | Ten-page chapter pagination | Pass | n/a |
| cloudflare | long-report | portable | Long table and repeated header | Pass | n/a |
| cloudflare | long-report | portable | Deterministic chart | Pass | n/a |
| bladepdf | long-report | portable | Ten-page chapter pagination | Pass | n/a |
| bladepdf | long-report | portable | Long table and repeated header | Pass | n/a |
| bladepdf | long-report | portable | Deterministic chart | Pass | n/a |
| browsershot | javascript-chart | portable | JavaScript canvas chart | Pass | n/a |
| browsershot | javascript-chart | portable | Delayed readiness content | Pass | n/a |
| dompdf | javascript-chart | portable | JavaScript canvas chart | Fail | Slightly misaligned text and missing line chart |
| dompdf | javascript-chart | portable | Delayed readiness content | Fail | Slightly misaligned text and missing line chart |
| gotenberg | javascript-chart | portable | JavaScript canvas chart | Pass | n/a |
| gotenberg | javascript-chart | portable | Delayed readiness content | Pass | n/a |
| cloudflare | javascript-chart | portable | JavaScript canvas chart | Fail | Missing line chart |
| cloudflare | javascript-chart | portable | Delayed readiness content | Fail | Missing line chart |
| bladepdf | javascript-chart | portable | JavaScript canvas chart | Pass | n/a |
| bladepdf | javascript-chart | portable | Delayed readiness content | Pass | n/a |
| browsershot | local-assets | native-path | public_path PNG | Pass | n/a |
| browsershot | local-assets | native-path | Vite CSS | Pass | n/a |
| browsershot | local-assets | native-path | storage_path WOFF2 | Pass | n/a |
| dompdf | local-assets | native-path | public_path PNG | Partial | Slightly misaligned text |
| dompdf | local-assets | native-path | Vite CSS | Partial | Slightly misaligned text |
| dompdf | local-assets | native-path | storage_path WOFF2 | Partial | Slightly misaligned text |
| gotenberg | local-assets | native-path | public_path PNG | Fail | Missing image and border |
| gotenberg | local-assets | native-path | Vite CSS | Fail | Missing image and border |
| gotenberg | local-assets | native-path | storage_path WOFF2 | Fail | Missing image and border |
| cloudflare | local-assets | native-path | public_path PNG | Fail | Missing image and border |
| cloudflare | local-assets | native-path | Vite CSS | Fail | Missing image and border |
| cloudflare | local-assets | native-path | storage_path WOFF2 | Fail | Missing image and border |
| bladepdf | local-assets | native-path | public_path PNG | Fail | Missing border |
| bladepdf | local-assets | native-path | Vite CSS | Fail | Missing border |
| bladepdf | local-assets | native-path | storage_path WOFF2 | Fail | Missing border |
| browsershot | local-assets | documented-remediation | public_path PNG | Pass | n/a |
| browsershot | local-assets | documented-remediation | Vite CSS | Pass | n/a |
| browsershot | local-assets | documented-remediation | storage_path WOFF2 | Pass | n/a |
| dompdf | local-assets | documented-remediation | public_path PNG | Partial | Slightly misaligned text |
| dompdf | local-assets | documented-remediation | Vite CSS | Partial | Slightly misaligned text |
| dompdf | local-assets | documented-remediation | storage_path WOFF2 | Partial | Slightly misaligned text |
| gotenberg | local-assets | documented-remediation | public_path PNG | Pass | n/a |
| gotenberg | local-assets | documented-remediation | Vite CSS | Pass | n/a |
| gotenberg | local-assets | documented-remediation | storage_path WOFF2 | Pass | n/a |
| cloudflare | local-assets | documented-remediation | public_path PNG | Pass | n/a |
| cloudflare | local-assets | documented-remediation | Vite CSS | Pass | n/a |
| cloudflare | local-assets | documented-remediation | storage_path WOFF2 | Pass | n/a |
| bladepdf | local-assets | documented-remediation | public_path PNG | Fail | Missing border |
| bladepdf | local-assets | documented-remediation | Vite CSS | Fail | Missing border |
| bladepdf | local-assets | documented-remediation | storage_path WOFF2 | Fail | Missing border |

## Direct-cost scenarios

Pricing snapshot verified at 2026-08-15; currency: USD; basis: tax-exclusive.

Prices exclude applicable taxes. Hetzner prices are for Germany (FSN/NBG), include one $0.60/month Cloud Primary IPv4, and exclude backups, snapshots, additional storage, and traffic overages.

| Monthly PDFs | Deployment | Renderer | Category | Target concurrency | Direct cost | Operational responsibility |
|---:|---|---|---|---:|---:|---|
| 1000 | [DOMPDF on existing Laravel capacity](https://github.com/dompdf/dompdf) | dompdf | existing-capacity | n/a | 0.00 | Medium |
| 1000 | [Browsershot on existing Laravel capacity](https://spatie.be/docs/browsershot/v4/requirements) | browsershot | existing-capacity | n/a | 0.00 | High |
| 1000 | [Gotenberg on existing container capacity](https://gotenberg.dev/docs/getting-started/installation) | gotenberg | existing-capacity | n/a | 0.00 | High |
| 1000 | [DOMPDF on a dedicated Hetzner CPX22 (budget c1)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | dompdf | dedicated-render-server | 1 | 23.59 | Medium |
| 1000 | [Browsershot on a dedicated Hetzner CPX22 (budget c1)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | browsershot | dedicated-render-server | 1 | 23.59 | High |
| 1000 | [Gotenberg on a dedicated Hetzner CPX22 (budget c1)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | gotenberg | dedicated-render-server | 1 | 23.59 | High |
| 1000 | [DOMPDF on a dedicated Hetzner CPX42 (budget c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | dompdf | dedicated-render-server | 8 | 82.59 | Medium |
| 1000 | [Browsershot on a dedicated Hetzner CPX42 (budget c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | browsershot | dedicated-render-server | 8 | 82.59 | High |
| 1000 | [Gotenberg on a dedicated Hetzner CPX42 (budget c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | gotenberg | dedicated-render-server | 8 | 82.59 | High |
| 1000 | [DOMPDF on a dedicated Hetzner CCX33 (control c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | dompdf | dedicated-render-server | 8 | 163.59 | Medium |
| 1000 | [Browsershot on a dedicated Hetzner CCX33 (control c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | browsershot | dedicated-render-server | 8 | 163.59 | High |
| 1000 | [Gotenberg on a dedicated Hetzner CCX33 (control c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | gotenberg | dedicated-render-server | 8 | 163.59 | High |
| 1000 | [Cloudflare Browser Run Quick Actions on Workers Paid](https://developers.cloudflare.com/browser-run/pricing/) | cloudflare | managed-service | n/a | 5.00 | Low |
| 1000 | [BladePDF Starter](https://bladepdf.com/) | bladepdf | managed-service | 1 | 12.00 | Low |
| 1000 | [BladePDF Scale](https://bladepdf.com/) | bladepdf | managed-service | 8 | 69.00 | Low |
| 10000 | [DOMPDF on existing Laravel capacity](https://github.com/dompdf/dompdf) | dompdf | existing-capacity | n/a | 0.00 | Medium |
| 10000 | [Browsershot on existing Laravel capacity](https://spatie.be/docs/browsershot/v4/requirements) | browsershot | existing-capacity | n/a | 0.00 | High |
| 10000 | [Gotenberg on existing container capacity](https://gotenberg.dev/docs/getting-started/installation) | gotenberg | existing-capacity | n/a | 0.00 | High |
| 10000 | [DOMPDF on a dedicated Hetzner CPX22 (budget c1)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | dompdf | dedicated-render-server | 1 | 23.59 | Medium |
| 10000 | [Browsershot on a dedicated Hetzner CPX22 (budget c1)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | browsershot | dedicated-render-server | 1 | 23.59 | High |
| 10000 | [Gotenberg on a dedicated Hetzner CPX22 (budget c1)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | gotenberg | dedicated-render-server | 1 | 23.59 | High |
| 10000 | [DOMPDF on a dedicated Hetzner CPX42 (budget c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | dompdf | dedicated-render-server | 8 | 82.59 | Medium |
| 10000 | [Browsershot on a dedicated Hetzner CPX42 (budget c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | browsershot | dedicated-render-server | 8 | 82.59 | High |
| 10000 | [Gotenberg on a dedicated Hetzner CPX42 (budget c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | gotenberg | dedicated-render-server | 8 | 82.59 | High |
| 10000 | [DOMPDF on a dedicated Hetzner CCX33 (control c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | dompdf | dedicated-render-server | 8 | 163.59 | Medium |
| 10000 | [Browsershot on a dedicated Hetzner CCX33 (control c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | browsershot | dedicated-render-server | 8 | 163.59 | High |
| 10000 | [Gotenberg on a dedicated Hetzner CCX33 (control c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | gotenberg | dedicated-render-server | 8 | 163.59 | High |
| 10000 | [Cloudflare Browser Run Quick Actions on Workers Paid](https://developers.cloudflare.com/browser-run/pricing/) | cloudflare | managed-service | n/a | 5.00 | Low |
| 10000 | [BladePDF Starter](https://bladepdf.com/) | bladepdf | managed-service | 1 | 12.00 | Low |
| 10000 | [BladePDF Scale](https://bladepdf.com/) | bladepdf | managed-service | 8 | 69.00 | Low |
| 100000 | [DOMPDF on existing Laravel capacity](https://github.com/dompdf/dompdf) | dompdf | existing-capacity | n/a | 0.00 | Medium |
| 100000 | [Browsershot on existing Laravel capacity](https://spatie.be/docs/browsershot/v4/requirements) | browsershot | existing-capacity | n/a | 0.00 | High |
| 100000 | [Gotenberg on existing container capacity](https://gotenberg.dev/docs/getting-started/installation) | gotenberg | existing-capacity | n/a | 0.00 | High |
| 100000 | [DOMPDF on a dedicated Hetzner CPX22 (budget c1)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | dompdf | dedicated-render-server | 1 | 23.59 | Medium |
| 100000 | [Browsershot on a dedicated Hetzner CPX22 (budget c1)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | browsershot | dedicated-render-server | 1 | 23.59 | High |
| 100000 | [Gotenberg on a dedicated Hetzner CPX22 (budget c1)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | gotenberg | dedicated-render-server | 1 | 23.59 | High |
| 100000 | [DOMPDF on a dedicated Hetzner CPX42 (budget c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | dompdf | dedicated-render-server | 8 | 82.59 | Medium |
| 100000 | [Browsershot on a dedicated Hetzner CPX42 (budget c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | browsershot | dedicated-render-server | 8 | 82.59 | High |
| 100000 | [Gotenberg on a dedicated Hetzner CPX42 (budget c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | gotenberg | dedicated-render-server | 8 | 82.59 | High |
| 100000 | [DOMPDF on a dedicated Hetzner CCX33 (control c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | dompdf | dedicated-render-server | 8 | 163.59 | Medium |
| 100000 | [Browsershot on a dedicated Hetzner CCX33 (control c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | browsershot | dedicated-render-server | 8 | 163.59 | High |
| 100000 | [Gotenberg on a dedicated Hetzner CCX33 (control c8)](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/) | gotenberg | dedicated-render-server | 8 | 163.59 | High |
| 100000 | [Cloudflare Browser Run Quick Actions on Workers Paid](https://developers.cloudflare.com/browser-run/pricing/) | cloudflare | managed-service | n/a | 5.00 | Low |
| 100000 | [BladePDF Starter](https://bladepdf.com/) | bladepdf | managed-service | 1 | 12.00 | Low |
| 100000 | [BladePDF Scale](https://bladepdf.com/) | bladepdf | managed-service | 8 | 69.00 | Low |

Direct costs exclude engineering and maintenance time. Operational responsibility is reported separately rather than converted to money.

## Operational methodology

See `operations.json` for the dated, source-linked installation, dependency, asset, and ownership declarations captured with this run.

## Artifacts

See `summary.json`, `summary.csv`, `raw/`, `resources/`, `pdfs/`, `screenshots/`, and `diffs/` in this run directory.
