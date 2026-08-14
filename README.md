# Laravel PDF Benchmark Suite

A reproducible, auditable Laravel application for comparing five PDF generation approaches through the same [`spatie/laravel-pdf`](https://spatie.be/docs/laravel-pdf/v2/introduction) interface:

- DOMPDF
- Browsershot
- Gotenberg
- Cloudflare Browser Run
- BladePDF

`browsershot-persistent` is measured as a separate tuning variant. It is never presented as a sixth core renderer or mixed into the primary five-renderer comparison.

This repository contains the harness and deterministic fixtures, not published performance claims. A publishable run must be executed on a named dedicated host, manually reviewed, and committed separately.

Prerequisites are Docker Engine with Docker Compose v2, GNU Make, Git, and enough disk space for the pinned Chromium and Gotenberg images. Local PHP, Composer, Node, Chrome, and qpdf installations are not required.

## Quick start

The supported workflow is deliberately small:

```bash
make setup
make preflight
make benchmark
make review RUN=20260809-eu-production
make report RUN=20260809-eu-production
```

Before `make preflight`, fill the required, non-secret metadata and any managed-provider credentials in `.env`:

```dotenv
BENCHMARK_HOST_LABEL=dedicated-4vcpu-8gb
BENCHMARK_REGION=Nuremberg, Germany
BENCHMARK_HOST_PROVIDER=hetzner
BENCHMARK_HOST_INSTANCE_TYPE=example-instance-type
BENCHMARK_HOST_CPU_ALLOCATION=shared
BENCHMARK_HOST_PURPOSE=budget
BENCHMARK_HOST_VCPU=4
BENCHMARK_HOST_MEMORY_MIB=8192
BENCHMARK_CLOUDFLARE_PLAN=paid-plan-name
BENCHMARK_BLADEPDF_PLAN=paid-plan-name
BENCHMARK_BLADEPDF_CONCURRENCY=10

CLOUDFLARE_ACCOUNT_ID=
CLOUDFLARE_API_TOKEN=
BLADEPDF_API_KEY=
```

Secrets stay in `.env` and are excluded from results. The preflight manifest never records a hostname or IP address. Values whose keys look secret are recursively redacted before JSON is written.

`BENCHMARK_HOST_CPU_ALLOCATION` accepts `shared`, `dedicated`, `bare-metal`, or `unknown`; `BENCHMARK_HOST_PURPOSE` accepts `budget`, `control`, or `development`. Preflight compares declared vCPU and RAM with what the container actually observes and rejects identifying host labels, including IP addresses and the real hostname.

## Canonical environment

Published numbers are intended to come from one dedicated Linux x86_64 host in an explicitly named EU region with at least 4 vCPU and 8 GB RAM. Docker Compose pins the application and its matching persistent Chromium service to `linux/amd64`; Gotenberg follows the native architecture selected from its pinned multi-platform digest. macOS and Apple Silicon are supported for development and smoke tests, but their numbers are not comparable and must not be published.

The dependency locks currently resolve the following primary versions:

| Component | Version |
| --- | ---: |
| Laravel | 13.24.0 |
| PHP | 8.4.1 image |
| `spatie/laravel-pdf` | 2.12.0 |
| Browsershot | 5.4.0 |
| DOMPDF | 3.1.6 |
| Puppeteer | 25.5.0 |
| BladePDF Spatie driver | 1.0.1 |
| `bladepdf/laravel` | 1.0.2 |
| Gotenberg | 8.34.0 Chromium image |

Composer and npm lock files are committed. Base images and Gotenberg use immutable multi-platform manifest digests in `Dockerfile` and `compose.yaml`. Preflight records the effective Git SHA/status, UTC time, CPU, RAM, kernel, architecture, Docker, PHP, Node, browser, Gotenberg and Composer package versions, along with the declared host/region/plan labels.

## Services

Docker Compose starts:

- `app`: Laravel, PHP, Node, Puppeteer, Poppler, qpdf, and the benchmark harness.
- `gotenberg`: the pinned Gotenberg Chromium service with its stock 8.34 limits made explicit (`max concurrency 6`, unbounded queue) and internal OTEL gauges enabled.
- `chromium-persistent`: the same application image and Puppeteer-managed browser used by standard Browsershot, exposed through the remote debugging protocol.

Standard Browsershot still launches a fresh browser for every render. Only the explicit `browsershot-persistent` variant uses `setRemoteInstance()`.

The application mounts the Docker socket read-only to capture service resource counters and to perform the controlled Gotenberg restart. Run the suite only on a benchmark host where this access is acceptable.

## What is rendered

All primary renders use this contract:

```php
Pdf::view($view, $data)
    ->driver($driver)
    ->dontCache()
    ->format('a4')
    ->generatePdfContent();
```

The benchmark replaces only Spatie's Cloudflare driver binding with a minimal subclass that appends `cacheTTL=0` to the Quick Actions PDF endpoint. [Cloudflare otherwise caches generated Quick Actions content for five seconds by default](https://developers.cloudflare.com/browser-run/faq/#is-there-any-temporary-caching-of-submitted-content), which would turn repeated deterministic fixtures into cache-hit measurements. The effective zero-second provider cache TTL is recorded in every run manifest. No other upstream driver behavior is changed.

The five deterministic fixtures use a fixed seed, date, neutral brand, and locally licensed assets:

| Fixture | Purpose |
| --- | --- |
| `simple-invoice` | Exactly two pages using CSS 2.1, tables, a PNG logo, and a manual page break. |
| `modern-invoice` | Two-page Tailwind document with Flexbox, Grid, CSS variables, gradients, SVG, Inter WOFF2, and rounded elements. |
| `long-report` | Ten reference pages with long tables, repeated headers, page labels, chapters, break rules, and deterministic charts. |
| `javascript-chart` | Delayed inline JavaScript, canvas chart, asynchronous font loading, and `window.pdfReady`. |
| `local-assets` | Literal Laravel `public_path()`, Vite CSS, and `storage_path()` font references. |

The two performance fixtures use only inline or data-URI assets so successful renderers receive equivalent documents. Asset fidelity is measured separately in two modes:

- `native-path`: the same unmodified Laravel view for every renderer.
- `documented-remediation`: the smallest renderer-specific remediation, with the strategy recorded in the run.

The current Spatie Gotenberg driver attaches HTML only, although Gotenberg's native API also supports multipart assets. The Cloudflare integration sends JSON HTML and does not expose an explicit readiness wait; the benchmark override changes only the Quick Actions cache TTL. These are capability outcomes, not harness failures. Browsershot, Gotenberg, and BladePDF use readiness waiting for the JavaScript fixture; DOMPDF intentionally does not execute JavaScript.

## Performance methodology

For each core renderer and both performance fixtures, the `full` profile runs:

1. one renderer-specific first observation;
2. 5 unmeasured warm-up renders;
3. 50 sequential measured renders;
4. 100 measured renders at concurrency 5;
5. 100 measured renders at concurrency 10.

First observations are labelled precisely:

| Renderer | Label |
| --- | --- |
| DOMPDF | fresh worker |
| Browsershot | fresh browser per render |
| Gotenberg | first request after controlled container restart |
| Cloudflare | first observed API request |
| BladePDF | first observed API request |

Scenario blocks are deterministically shuffled from the recorded seed. Workers boot Laravel before the timed barrier and receive balanced iteration lists. A cooldown is recorded between blocks. Spatie caching and application retries are disabled; BladePDF is configured for one HTTP attempt. Timeouts, HTTP 429 responses, and other errors count toward failure rate and are not retried.

Each observation records wall latency, PDF size/hash, request and response bytes, HTTP status, safe response headers, and a sanitized error type/message. Summaries use nearest-rank p50/p95/p99, success/failure/timeout counts, and both attempted and successful throughput. A partial block remains in raw data but is excluded; `--resume` creates a new attempt, and reports select the latest complete attempt.

The command can be narrowed without changing the schema:

```bash
docker compose run --rm app php artisan benchmark:run \
  --profile=full \
  --renderers=dompdf,browsershot,gotenberg \
  --templates=simple-invoice \
  --seed=20260809 \
  --run-id=20260809-eu-production
```

`--allow-dirty` exists for local smoke testing only. Publishable runs should use a clean worktree.

To continue an interrupted run without merging partial blocks, use the same options and environment:

```bash
make benchmark RUN=20260809-eu-production RESUME=1
```

## Resource measurements

The harness samples worker process trees from `/proc` and Docker cgroup counters throughout each block:

- application: aggregate peak RSS, CPU seconds, process count, and PHP/Node/Chrome breakdown;
- render service: Gotenberg or persistent Chromium memory, processes, and CPU delta;
- external provider: `null`, with an explicit note that provider-side resource use is not observable.

Preflight also records Docker image sizes. Run artifacts and benchmark temporary storage are measured separately. These component boundaries matter more than combining unrelated local and provider resources into one number.

CPU seconds are normalized by block duration and detected logical CPUs. Each measured block reports configured concurrency, peak and average in-flight renders, worker-pool utilization, sample standard deviation, and latency coefficient of variation. Gotenberg 8.34 exports `chromium_requests_active` and `chromium_requests_queue_size` through its internal OpenTelemetry Prometheus endpoint; the harness samples both without exposing the endpoint outside the Compose network. DOMPDF and standard Browsershot have no separate render-service queue. Managed-provider queue depth is not observable and remains `null`.

## Server capacity experiments

The original `full` profile remains unchanged so its 50 sequential, concurrency-5, and concurrency-10 results stay methodologically comparable. Server sizing uses the separate `capacity` profile:

```bash
# Small budget host or one-concurrency managed plan
make benchmark PROFILE=capacity CONCURRENCY=1 ITERATIONS=100 \
  RUN=budget-small-c1

# Shared-vCPU budget host
make benchmark PROFILE=capacity CONCURRENCY=1,2,4,8,12,16 ITERATIONS=100 \
  RENDERERS=dompdf,browsershot,gotenberg,browsershot-persistent \
  RUN=budget-multi-self-hosted

# Managed plan with a declared limit of eight
make benchmark PROFILE=capacity CONCURRENCY=1,2,4,8 ITERATIONS=100 \
  RENDERERS=bladepdf \
  RUN=managed-scale-c8
```

The default capacity sweep is `1,2,4,8,12,16`; both the levels and iteration count are stored in `manifest.json` and are part of resume compatibility. The report shows the observed throughput peak, highest failure-free tested level, first failure/timeout level, CPU/RAM, Gotenberg queue peak, and latency variability. It deliberately does not infer that eight vCPU means eight safe concurrent renders or automatically assign a production-safe limit.

Run each physical server, CPU class, and managed plan as a separate run. A practical publication set can include a small shared-vCPU budget host, a larger shared-vCPU budget host, and matching dedicated-vCPU control hosts. Shared-vCPU data answers "what did this inexpensive server deliver?"; dedicated-vCPU controls help expose noisy-neighbor variability. Do not merge those observations or describe shared-vCPU output as hardware-normalized.

The timed load generator is the fixed Laravel worker pool on the benchmark host. It is co-located with the application by design, so PHP/Node/local Chromium consumption is observable. A separate machine may orchestrate the command over SSH, but it is not in the timed request path. Do not claim a separate load generator in the article unless a future distributed harness implements and records one.

## Fidelity and manual review

Representative PDFs are checked with `qpdf --check`, `pdfinfo`, `pdffonts`, and `pdftotext`. Poppler renders every page at a fixed 144 DPI. Browsershot output from the same run is used as a visual reference, not assumed to be correct.

The harness creates first-page images, every page PNG, overlays, pixel diffs, page-count mismatches, and feature-level crop metrics with an antialiasing tolerance. Start the loopback-only review UI with:

```bash
make review RUN=20260809-eu-production
```

For every core renderer feature, record `Pass`, `Partial`, or `Fail`, plus a concrete problem and optional note. `make report` refuses to create a final report while any core feature is unreviewed. A missing or invalid renderer output still receives review entries and should normally be marked `Fail`.

## Results layout

Each `results/runs/<run-id>` contains:

```text
manifest.json
operations.json
raw/observations.jsonl
raw/blocks.jsonl
raw/attempts/
raw/fidelity.json
resources/resources.jsonl
summary.json
summary.csv
pdfs/
screenshots/
diffs/
text/
fidelity-review.json
REPORT.md
```

Working runs are ignored by Git. After review and report generation, explicitly copy the selected run to `results/published/<run-id>`, inspect it for secrets, and commit that one immutable run. Never merge observations from different machines, regions, plans, or seeds.

## Operational and cost data

`ops/operations.json` is a dated, source-linked operational manifest covering dependencies, asset strategy, queue/concurrency/storage/retry/monitoring ownership, and Low/Medium/High responsibility. It is copied into every run.

The optional clean-room installation experiment is intentionally outside the performance workflow:

```bash
make ops-install
```

This command uses the isolated `laravel-pdf-benchmark-ops` Compose project and removes only that project's named volumes before and after the experiment.

Direct-cost scenarios do not contain repository-maintained prices. Copy `ops/costs.example.json` to the ignored `ops/costs.local.json`, enter a dated snapshot, and run `make report RUN=... COSTS=ops/costs.local.json`.

The snapshot supports multiple deployment rows for the same renderer and keeps these views separate:

- existing server with spare capacity, potentially zero incremental infrastructure cost;
- enlarged application server, priced as the difference from the previous server;
- dedicated rendering server, priced at its full monthly cost;
- managed service or plan, with no customer-operated rendering server.

Every deployment records a category, target concurrency, operational responsibility, source URL, tax basis, and optional infrastructure details. Direct-cost calculations include fixed/server price, PDF usage, measured browser time, average browser-concurrency charges, storage retention, and egress. Cloudflare can use measured `X-Browser-Ms-Used`; included browser milliseconds and included/assumed average concurrency remain explicit snapshot inputs. The report produces separate 1k, 10k, and 100k PDF scenarios and a component breakdown without assigning a dollar value to engineering time.

Use tax-exclusive figures for the primary comparison unless there is a specific reason not to, and state whether a server price includes Primary IPv4. `verified_at` is mandatory. Current Hetzner, Cloudflare, and BladePDF prices or limits must be checked again on that date rather than copied from this repository.

## CI and local verification

CI runs Pint, PHPStan, PHPUnit, asset compilation, and functional smoke tests for DOMPDF, standard Browsershot, and Gotenberg. Cloudflare and BladePDF are mocked in tests. GitHub-hosted runners are never used to produce publishable performance numbers.

```bash
make test
make ci
make smoke
```

See `CONTRIBUTING.md` for test expectations and methodology-change rules.

## License

The benchmark suite is released under the MIT License. Inter is included under the SIL Open Font License; see `storage/app/fonts/OFL-Inter.txt`.
