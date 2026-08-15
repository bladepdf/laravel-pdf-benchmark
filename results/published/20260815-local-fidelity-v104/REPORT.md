# Laravel PDF Benchmark Report

Run: `20260815-local-fidelity-v104`  
Profile: `fidelity`  
Generated: 2026-08-15T16:34:22+00:00  
Host label: local-docker-fidelity  
Region: Prague, Czechia  
Infrastructure: local docker-desktop; 4 vCPU (unknown); 7837 MiB RAM; purpose: development.  
Load generation: in-process worker pool, co-located with the application on the benchmark host.  
Git: `36be2fba7080dc2c3db2cc45a3ca362060e96817`  
Cloudflare plan: workers-paid; BladePDF plan: scale; declared BladePDF concurrency: 8.

Percentiles: nearest-rank; application cache and retries disabled; Cloudflare Quick Actions cache disabled with `cacheTTL=0`.

> Provider-side resource consumption is not observable, so only application-side usage was measured for managed services.

> CPU counters are retained in the raw artifacts for diagnostics, but are omitted from comparative tables because short-lived application child processes may exit between samples and be undercounted. No CPU values are estimated or simulated.

## Core renderer performance

| Renderer | Variant | Template | Scenario / first-observation label | Concurrency | Success | Fail | Timeout | p50 ms | p95 ms | p99 ms | CV % | Peak in-flight | PDF KiB | Successful PDF/s | App peak MiB | Render service peak MiB | Queue peak |
|---|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|


## Reviewed fidelity features

| Renderer | Template | Mode | Feature | Result | Problem |
|---|---|---|---|---|---|
| browsershot | simple-invoice | portable | Table layout | Pass | n/a |
| browsershot | simple-invoice | portable | Two-page manual break | Pass | n/a |
| browsershot | simple-invoice | portable | Portable PNG logo | Pass | n/a |
| dompdf | simple-invoice | portable | Table layout | Partial | Slight padding/offset mismatch |
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
| dompdf | modern-invoice | portable | Gradient and Flexbox | Fail | Missing gradient and likely incorrect flexbox layout |
| dompdf | modern-invoice | portable | CSS Grid | Fail | Incorrect CSS grid layout |
| dompdf | modern-invoice | portable | Inline SVG | Fail | SVG images are not rendering correctly |
| dompdf | modern-invoice | portable | Inter WOFF2 | Fail | Looks like a different font is being used |
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
| dompdf | long-report | portable | Ten-page chapter pagination | Pass | n/a |
| dompdf | long-report | portable | Long table and repeated header | Pass | n/a |
| dompdf | long-report | portable | Deterministic chart | Fail | The chart is not rendering |
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
| dompdf | javascript-chart | portable | JavaScript canvas chart | Fail | The canvas chart is not rendering |
| dompdf | javascript-chart | portable | Delayed readiness content | Fail | Delayed readiness is not working |
| gotenberg | javascript-chart | portable | JavaScript canvas chart | Pass | n/a |
| gotenberg | javascript-chart | portable | Delayed readiness content | Pass | n/a |
| cloudflare | javascript-chart | portable | JavaScript canvas chart | Fail | The canvas chart is not rendering |
| cloudflare | javascript-chart | portable | Delayed readiness content | Fail | Delayed readiness is not working |
| bladepdf | javascript-chart | portable | JavaScript canvas chart | Pass | n/a |
| bladepdf | javascript-chart | portable | Delayed readiness content | Pass | n/a |
| browsershot | local-assets | native-path | public_path PNG | Pass | n/a |
| browsershot | local-assets | native-path | Vite CSS | Pass | n/a |
| browsershot | local-assets | native-path | storage_path WOFF2 | Pass | n/a |
| dompdf | local-assets | native-path | public_path PNG | Pass | n/a |
| dompdf | local-assets | native-path | Vite CSS | Pass | n/a |
| dompdf | local-assets | native-path | storage_path WOFF2 | Fail | Looks like a different font is being used |
| gotenberg | local-assets | native-path | public_path PNG | Fail | The image is not rendering |
| gotenberg | local-assets | native-path | Vite CSS | Fail | Vite CSS is not working |
| gotenberg | local-assets | native-path | storage_path WOFF2 | Fail | Looks like a different font is being used |
| cloudflare | local-assets | native-path | public_path PNG | Fail | The image is not rendering |
| cloudflare | local-assets | native-path | Vite CSS | Fail | Vite CSS is not working |
| cloudflare | local-assets | native-path | storage_path WOFF2 | Fail | Looks like a different font is being used |
| bladepdf | local-assets | native-path | public_path PNG | Pass | n/a |
| bladepdf | local-assets | native-path | Vite CSS | Pass | n/a |
| bladepdf | local-assets | native-path | storage_path WOFF2 | Fail | Looks like a different font is being used |
| browsershot | local-assets | documented-remediation | public_path PNG | Pass | n/a |
| browsershot | local-assets | documented-remediation | Vite CSS | Pass | n/a |
| browsershot | local-assets | documented-remediation | storage_path WOFF2 | Pass | n/a |
| dompdf | local-assets | documented-remediation | public_path PNG | Pass | n/a |
| dompdf | local-assets | documented-remediation | Vite CSS | Pass | n/a |
| dompdf | local-assets | documented-remediation | storage_path WOFF2 | Fail | Looks like a different font is being used |
| gotenberg | local-assets | documented-remediation | public_path PNG | Pass | n/a |
| gotenberg | local-assets | documented-remediation | Vite CSS | Pass | n/a |
| gotenberg | local-assets | documented-remediation | storage_path WOFF2 | Pass | n/a |
| cloudflare | local-assets | documented-remediation | public_path PNG | Pass | n/a |
| cloudflare | local-assets | documented-remediation | Vite CSS | Pass | n/a |
| cloudflare | local-assets | documented-remediation | storage_path WOFF2 | Pass | n/a |
| bladepdf | local-assets | documented-remediation | public_path PNG | Pass | n/a |
| bladepdf | local-assets | documented-remediation | Vite CSS | Pass | n/a |
| bladepdf | local-assets | documented-remediation | storage_path WOFF2 | Pass | n/a |

## Operational methodology

See `operations.json` for the dated, source-linked installation, dependency, asset, and ownership declarations captured with this run.

## Artifacts

See `summary.json`, `summary.csv`, `raw/`, `resources/`, `pdfs/`, `screenshots/`, and `diffs/` in this run directory.
