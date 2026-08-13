<?php

namespace App\Benchmark;

use RuntimeException;

final class ReportGenerator
{
    public function __construct(
        private readonly RunStore $store,
        private readonly CostCalculator $costs,
        private readonly BenchmarkMatrix $matrix,
    ) {}

    /** @return array<string, mixed> */
    public function generate(string $runId, bool $allowUnreviewed = false, ?string $costSnapshotPath = null): array
    {
        $root = $this->store->path($runId);
        $manifest = $this->json($root.'/manifest.json');
        $review = is_file($root.'/fidelity-review.json') ? $this->json($root.'/fidelity-review.json') : null;
        if (in_array($manifest['profile'], ['full', 'fidelity'], true) && $review === null) {
            throw new RuntimeException('The fidelity review manifest is missing. Run the fidelity processor before reporting.');
        }
        if ($review !== null && ! $allowUnreviewed) {
            $unchecked = array_filter($review['entries'], fn (array $entry) => $entry['status'] === null);
            if ($unchecked !== []) {
                throw new RuntimeException(count($unchecked).' core fidelity features are unreviewed. Run benchmark:review first.');
            }
            $invalid = array_filter($review['entries'], fn (array $entry) => ! in_array($entry['status'], ['pass', 'partial', 'fail'], true)
                || (in_array($entry['status'], ['partial', 'fail'], true) && trim((string) ($entry['problem'] ?? '')) === ''));
            if ($invalid !== []) {
                throw new RuntimeException(count($invalid).' fidelity reviews are invalid or missing a concrete problem.');
            }
        }

        $complete = $this->store->latestCompleteBlocks($runId);
        $expectedKeys = $this->expectedBlockKeys($manifest);
        $missingBlocks = array_diff($expectedKeys, array_keys($complete));
        if ($missingBlocks !== []) {
            throw new RuntimeException(count($missingBlocks).' expected scenario blocks have no complete attempt. Resume the run first.');
        }
        $observations = $this->store->readJsonLines($runId, 'raw/observations.jsonl');
        $resources = $this->latestResources($runId);
        $rows = [];

        foreach ($complete as $blockKey => $block) {
            $selected = array_values(array_filter($observations, fn (array $observation) => $this->observationKey($observation) === $blockKey && $observation['attempt'] === $block['attempt']
            ));
            $durationSeconds = (float) $block['duration_seconds'];
            $configuredConcurrency = (int) $block['block']['concurrency'];
            $resource = $resources[$blockKey.'__'.$block['attempt']] ?? null;
            $normalizedResources = $this->normalizedResources(
                $resource,
                $durationSeconds,
                (int) ($manifest['environment']['platform']['cpu_logical'] ?? 0),
            );
            $declaredConcurrencyLimit = $block['block']['renderer'] === 'bladepdf'
                ? (int) ($manifest['environment']['declared']['bladepdf_concurrency'] ?? 0)
                : null;
            $summary = Statistics::summarize($selected, $durationSeconds, $configuredConcurrency);
            $rows[] = [
                'renderer' => $block['block']['renderer'],
                'variant' => $block['block']['variant'],
                'template' => $block['block']['template'],
                'asset_mode' => $block['block']['asset_mode'],
                'scenario' => $block['block']['slug'],
                'phase' => $block['block']['phase'],
                'concurrency' => $configuredConcurrency,
                'measured' => $block['block']['measured'],
                'attempt' => $block['attempt'],
                'duration_seconds' => $block['duration_seconds'],
                'cold_label' => config("benchmark.renderers.{$block['block']['renderer']}.cold_label"),
                'topology' => config("benchmark.renderers.{$block['block']['renderer']}.topology"),
                'declared_concurrency_limit' => $declaredConcurrencyLimit ?: null,
                'over_declared_concurrency' => $declaredConcurrencyLimit === null ? null : $configuredConcurrency > $declaredConcurrencyLimit,
                ...$summary,
                'resources' => $normalizedResources,
            ];
        }
        usort($rows, fn (array $a, array $b) => [$a['renderer'], $a['template'], $a['phase'], $a['concurrency'], $a['scenario']] <=> [$b['renderer'], $b['template'], $b['phase'], $b['concurrency'], $b['scenario']]);

        $summary = [
            'schema_version' => 1,
            'run_id' => $runId,
            'generated_at' => now('UTC')->toIso8601String(),
            'manifest' => $manifest,
            'methodology' => [
                'percentile' => 'nearest-rank',
                'cache' => 'disabled',
                'retries' => 0,
                'visual_reference' => 'browsershot from the same run; not an automatic correctness oracle',
                'load_generation' => 'fixed in-process worker pool with no application queue',
                'queue_observability' => 'Gotenberg queue depth is sampled from its OTEL gauge; managed-provider internal queues are not observable.',
                'latency_variability' => 'sample standard deviation and coefficient of variation over successful observations',
            ],
            'results' => $rows,
            'fidelity_review' => $review,
        ];

        $this->assertNoSecrets($root);

        $costPath = $costSnapshotPath ?? base_path('ops/costs.local.json');
        if (is_file($costPath)) {
            $summary['costs'] = $this->costs->calculate($this->json($costPath), $this->costMetrics($rows, $observations));
        } else {
            $summary['costs'] = null;
            $summary['cost_note'] = 'No local, uncommitted pricing snapshot was provided.';
        }

        $this->store->write($runId, 'summary.json', $summary);
        $this->writeCsv($root.'/summary.csv', $rows);
        file_put_contents($root.'/REPORT.md', $this->markdown($summary), LOCK_EX);

        return $summary;
    }

    /** @return array<string, array<string, mixed>> */
    private function latestResources(string $runId): array
    {
        $latest = [];
        foreach ($this->store->readJsonLines($runId, 'resources/resources.jsonl') as $row) {
            $latest[$row['block_key'].'__'.$row['attempt']] = $row;
        }

        return $latest;
    }

    /** @param array<string, mixed> $observation */
    private function observationKey(array $observation): string
    {
        return implode('__', [
            $observation['renderer'], $observation['variant'], $observation['template'], $observation['asset_mode'], $observation['scenario'],
        ]);
    }

    /** @param list<array<string, mixed>> $rows */
    private function writeCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'wb');
        $headers = [
            'renderer', 'variant', 'template', 'asset_mode', 'scenario', 'phase', 'measured', 'attempt',
            'concurrency', 'cold_label', 'duration_seconds', 'attempted', 'successful', 'failures', 'timeouts', 'p50_ms', 'p95_ms', 'p99_ms',
            'mean_ms', 'stddev_ms', 'cv_pct', 'peak_in_flight', 'average_in_flight', 'worker_utilization_pct',
            'mean_pdf_bytes', 'attempted_throughput', 'successful_throughput', 'application_peak_rss_bytes',
            'application_cpu_utilization_pct', 'render_service_peak_memory_bytes', 'render_service_cpu_utilization_pct',
            'observed_cpu_utilization_pct', 'render_service_peak_active_requests', 'render_service_peak_queue_size',
            'declared_concurrency_limit', 'over_declared_concurrency',
        ];
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['renderer'], $row['variant'], $row['template'], $row['asset_mode'], $row['scenario'], $row['phase'],
                $row['measured'] ? 'true' : 'false', $row['attempt'], $row['concurrency'], $row['cold_label'], $row['duration_seconds'], $row['attempted'], $row['successful'],
                $row['failures'], $row['timeouts'], $row['p50_ms'], $row['p95_ms'], $row['p99_ms'],
                $row['mean_ms'], $row['stddev_ms'], $row['cv_pct'], $row['peak_in_flight'], $row['average_in_flight'], $row['worker_utilization_pct'],
                $row['mean_pdf_bytes'],
                $row['attempted_throughput'], $row['successful_throughput'],
                $row['resources']['application']['aggregate_rss_bytes'] ?? null,
                $row['resources']['application']['cpu_utilization_pct'] ?? null,
                $row['resources']['render_service']['memory_bytes'] ?? null,
                $row['resources']['render_service']['cpu_utilization_pct'] ?? null,
                $row['resources']['observed_cpu_utilization_pct'] ?? null,
                $row['resources']['render_service']['active_requests'] ?? null,
                $row['resources']['render_service']['queue_size'] ?? null,
                $row['declared_concurrency_limit'],
                $row['over_declared_concurrency'] === null ? null : ($row['over_declared_concurrency'] ? 'true' : 'false'),
            ]);
        }
        fclose($handle);
    }

    /** @param array<string, mixed> $summary */
    private function markdown(array $summary): string
    {
        $declared = $summary['manifest']['environment']['declared'];
        $git = $summary['manifest']['environment']['git'];
        $lines = [
            '# Laravel PDF Benchmark Report',
            '',
            "Run: `{$summary['run_id']}`  ",
            'Profile: `'.$summary['manifest']['profile'].'`  ',
            "Generated: {$summary['generated_at']}  ",
            'Host label: '.$declared['host_label'].'  ',
            'Region: '.$declared['region'].'  ',
            'Infrastructure: '.$declared['host_provider'].' '.$declared['host_instance_type'].'; '.$declared['host_vcpu'].' vCPU ('.$declared['host_cpu_allocation'].'); '.$declared['host_memory_mib'].' MiB RAM; purpose: '.$declared['host_purpose'].'.  ',
            'Load generation: in-process worker pool, co-located with the application on the benchmark host.  ',
            'Git: `'.$git['sha'].'`'.($git['dirty'] ? ' (dirty)' : '').'  ',
            'Cloudflare plan: '.$declared['cloudflare_plan'].'; BladePDF plan: '.$declared['bladepdf_plan'].'; declared BladePDF concurrency: '.$declared['bladepdf_concurrency'].'.',
            '',
            'Percentiles: nearest-rank; application cache and retries disabled.',
            '',
            '> Provider-side resource consumption is not observable, so only application-side usage was measured for managed services.',
            '',
        ];

        $coreRows = array_values(array_filter(
            $summary['results'],
            fn (array $row) => (bool) config("benchmark.renderers.{$row['renderer']}.core"),
        ));
        $tuningRows = array_values(array_filter(
            $summary['results'],
            fn (array $row) => ! (bool) config("benchmark.renderers.{$row['renderer']}.core"),
        ));
        $lines = [...$lines, ...$this->performanceMarkdown('Core renderer performance', $coreRows)];
        if ($tuningRows !== []) {
            $lines = [...$lines, ...$this->performanceMarkdown('Secondary tuning variants', $tuningRows)];
        }
        if ($summary['manifest']['profile'] === 'capacity') {
            $lines = [...$lines, ...$this->capacityMarkdown($summary['results'])];
        }

        if ($summary['fidelity_review'] !== null) {
            $lines[] = '';
            $lines[] = '## Reviewed fidelity features';
            $lines[] = '';
            $lines[] = '| Renderer | Template | Mode | Feature | Result | Problem |';
            $lines[] = '|---|---|---|---|---|---|';
            foreach ($summary['fidelity_review']['entries'] as $entry) {
                $lines[] = '| '.implode(' | ', array_map(
                    fn ($value) => str_replace('|', '\\|', (string) ($value ?? 'n/a')),
                    [$entry['renderer'], $entry['template'], $entry['asset_mode'], $entry['label'], ucfirst((string) $entry['status']), $entry['problem']],
                )).' |';
            }
        }

        if ($summary['costs'] !== null) {
            $lines[] = '';
            $lines[] = '## Direct-cost scenarios';
            $lines[] = '';
            $lines[] = 'Pricing snapshot verified at '.$summary['costs']['verified_at'].'; currency: '.$summary['costs']['currency'].'; basis: '.$summary['costs']['price_basis'].'.';
            if ($summary['costs']['tax_note'] !== null) {
                $lines[] = '';
                $lines[] = $summary['costs']['tax_note'];
            }
            $lines[] = '';
            $lines[] = '| Monthly PDFs | Deployment | Renderer | Category | Target concurrency | Direct cost | Operational responsibility |';
            $lines[] = '|---:|---|---|---|---:|---:|---|';
            foreach ($summary['costs']['scenarios'] as $volume => $deployments) {
                foreach ($deployments as $cost) {
                    $lines[] = '| '.implode(' | ', [
                        $volume,
                        '['.str_replace('|', '\\|', $cost['label']).']('.$cost['source_url'].')',
                        $cost['renderer'],
                        $cost['category'],
                        $cost['target_concurrency'] ?? 'n/a',
                        $this->number($cost['direct_cost']),
                        $cost['operational_responsibility'],
                    ]).' |';
                }
            }
            $lines[] = '';
            $lines[] = 'Direct costs exclude engineering and maintenance time. Operational responsibility is reported separately rather than converted to money.';
        }

        $lines[] = '';
        $lines[] = '## Operational methodology';
        $lines[] = '';
        $lines[] = 'See `operations.json` for the dated, source-linked installation, dependency, asset, and ownership declarations captured with this run.';

        $lines[] = '';
        $lines[] = '## Artifacts';
        $lines[] = '';
        $lines[] = 'See `summary.json`, `summary.csv`, `raw/`, `resources/`, `pdfs/`, `screenshots/`, and `diffs/` in this run directory.';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<string>
     */
    private function performanceMarkdown(string $heading, array $rows): array
    {
        $lines = [
            '## '.$heading,
            '',
            '| Renderer | Variant | Template | Scenario / first-observation label | Concurrency | Success | Fail | Timeout | p50 ms | p95 ms | p99 ms | CV % | Peak in-flight | PDF KiB | Successful PDF/s | Observed CPU % | App peak MiB | Render service peak MiB | Queue peak |',
            '|---|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|',
        ];

        foreach ($rows as $row) {
            if (! $row['measured']) {
                continue;
            }
            $scenario = $row['scenario'] === 'first' ? 'first - '.$row['cold_label'] : $row['scenario'];
            if ($row['over_declared_concurrency'] === true) {
                $scenario .= ' [over declared plan limit '.$row['declared_concurrency_limit'].']';
            }
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %d | %d/%d | %d | %d | %s | %s | %s | %s | %d | %s | %s | %s | %s | %s | %s |',
                $row['renderer'], $row['variant'], $row['template'], $scenario, $row['concurrency'], $row['successful'], $row['attempted'],
                $row['failures'], $row['timeouts'], $this->number($row['p50_ms']), $this->number($row['p95_ms']),
                $this->number($row['p99_ms']), $this->number($row['cv_pct']), $row['peak_in_flight'],
                $this->bytesToUnit($row['mean_pdf_bytes']), $this->number($row['successful_throughput']),
                $this->number($row['resources']['observed_cpu_utilization_pct'] ?? null),
                $this->bytesToUnit($row['resources']['application']['aggregate_rss_bytes'] ?? null, 1_048_576),
                $this->bytesToUnit($row['resources']['render_service']['memory_bytes'] ?? null, 1_048_576),
                $this->number($row['resources']['render_service']['queue_size'] ?? null),
            );
        }

        $lines[] = '';

        return $lines;
    }

    private function bytesToUnit(float|int|null $bytes, int $divisor = 1024): string
    {
        return $bytes === null ? 'n/a' : number_format($bytes / $divisor, 2, '.', '');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $observations
     * @return array<string, array<string, mixed>>
     */
    private function costMetrics(array $rows, array $observations): array
    {
        $metrics = [];
        foreach ($rows as $row) {
            if ($row['template'] === 'simple-invoice'
                && in_array($row['scenario'], ['sequential', 'concurrency-1'], true)
                && $row['successful'] > 0) {
                $metrics[$row['renderer']]['mean_pdf_bytes'] = $row['mean_pdf_bytes'];
            }
        }
        foreach ($observations as $observation) {
            $browserMs = $observation['response_headers']['x-browser-ms-used'] ?? null;
            if ($browserMs !== null
                && $observation['template'] === 'simple-invoice'
                && in_array($observation['scenario'], ['sequential', 'concurrency-1'], true)
                && $observation['status'] === 'success') {
                $metrics[$observation['renderer']]['browser_ms'][] = (float) $browserMs;
            }
        }
        foreach ($metrics as &$metric) {
            if (isset($metric['browser_ms'])) {
                $metric['mean_browser_ms'] = array_sum($metric['browser_ms']) / count($metric['browser_ms']);
                unset($metric['browser_ms']);
            }
        }

        return $metrics;
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Missing required result file: {$path}");
        }

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    private function number(float|int|null $number): string
    {
        return $number === null ? 'n/a' : number_format((float) $number, 2, '.', '');
    }

    /** @param array<string, mixed> $manifest
     * @return list<string>
     */
    private function expectedBlockKeys(array $manifest): array
    {
        $blocks = $this->matrix->build(
            $manifest['profile'],
            $manifest['renderers'],
            $manifest['templates'],
            $manifest['seed'],
            $manifest['matrix_options'] ?? [],
        );
        if ($manifest['profile'] === 'full') {
            $blocks = [
                ...$blocks,
                ...$this->matrix->build('fidelity', $manifest['renderers'], $manifest['templates'], $manifest['seed'] + 1),
            ];
        }

        return array_values(array_unique(array_map(BenchmarkMatrix::key(...), $blocks)));
    }

    /**
     * @param  array<string, mixed>|null  $resource
     * @return array<string, mixed>|null
     */
    private function normalizedResources(?array $resource, float $durationSeconds, int $logicalCpu): ?array
    {
        if ($resource === null) {
            return null;
        }

        $divisor = $durationSeconds > 0 && $logicalCpu > 0 ? $durationSeconds * $logicalCpu : null;
        $applicationCpu = $divisor === null
            ? null
            : ((float) ($resource['application']['cpu_seconds_total'] ?? 0) / $divisor) * 100;
        $serviceCpu = $divisor === null || ($resource['render_service'] ?? null) === null
            ? null
            : ((float) ($resource['render_service']['cpu_seconds_delta'] ?? 0) / $divisor) * 100;

        $resource['application']['cpu_utilization_pct'] = $applicationCpu;
        if (($resource['render_service'] ?? null) !== null) {
            $resource['render_service']['cpu_utilization_pct'] = $serviceCpu;
        }
        $resource['observed_cpu_utilization_pct'] = $applicationCpu === null
            ? null
            : $applicationCpu + ($serviceCpu ?? 0);

        return $resource;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<string>
     */
    private function capacityMarkdown(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            if ($row['phase'] !== 'measured' || ! str_starts_with($row['scenario'], 'concurrency-')) {
                continue;
            }
            $groups[$row['renderer'].'__'.$row['variant'].'__'.$row['template']][] = $row;
        }

        $lines = [
            '## Capacity sweep observations',
            '',
            'These are observed limits, not a CPU-to-concurrency assumption. Throughput peak and first failure level are descriptive; the report does not assign an automatic production-safe concurrency.',
            '',
            '| Renderer | Variant | Template | Tested levels | Declared plan limit | Highest failure-free within limit | Throughput peak at | First failure/timeout at |',
            '|---|---|---|---|---:|---:|---:|---:|',
        ];
        foreach ($groups as $group) {
            usort($group, fn (array $left, array $right) => $left['concurrency'] <=> $right['concurrency']);
            $failureFree = array_values(array_filter($group, fn (array $row) => $row['failures'] === 0 && $row['timeouts'] === 0));
            $declaredLimit = $group[0]['declared_concurrency_limit'];
            $failureFreeWithinLimit = array_values(array_filter(
                $failureFree,
                fn (array $row) => $declaredLimit === null || $row['concurrency'] <= $declaredLimit,
            ));
            $peak = $group;
            usort($peak, fn (array $left, array $right) => $right['successful_throughput'] <=> $left['successful_throughput']);
            $firstFailure = array_find($group, fn (array $row) => $row['failures'] > 0 || $row['timeouts'] > 0);
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s | %s | %d | %s |',
                $group[0]['renderer'],
                $group[0]['variant'],
                $group[0]['template'],
                implode(', ', array_column($group, 'concurrency')),
                $declaredLimit ?? 'n/a',
                $failureFreeWithinLimit === [] ? 'n/a' : (string) max(array_column($failureFreeWithinLimit, 'concurrency')),
                $peak[0]['concurrency'],
                $firstFailure === null ? 'n/a' : (string) $firstFailure['concurrency'],
            );
        }
        $lines[] = '';

        return $lines;
    }

    private function assertNoSecrets(string $root): void
    {
        $secrets = array_values(array_filter([
            config('bladepdf.api_key'),
            config('laravel-pdf.cloudflare.api_token'),
            config('laravel-pdf.cloudflare.account_id'),
            config('laravel-pdf.gotenberg.password'),
        ], fn (mixed $value) => is_string($value) && strlen($value) >= 4));

        if ($secrets === []) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['json', 'jsonl', 'csv', 'md', 'txt'], true)) {
                continue;
            }
            $contents = (string) file_get_contents($file->getPathname());
            foreach ($secrets as $secret) {
                if (str_contains($contents, $secret)) {
                    throw new RuntimeException('A configured credential was found in a text result artifact. Publication is blocked.');
                }
            }
        }
    }
}
