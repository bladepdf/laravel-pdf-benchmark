<?php

namespace App\Console\Commands;

use App\Benchmark\BenchmarkMatrix;
use App\Benchmark\DockerRuntime;
use App\Benchmark\EnvironmentCollector;
use App\Benchmark\FidelityProcessor;
use App\Benchmark\HostMetadataValidator;
use App\Benchmark\ProcessResourceSampler;
use App\Benchmark\RunStore;
use App\Benchmark\SchemaValidator;
use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Process\Process;

final class BenchmarkRunCommand extends Command
{
    protected $signature = 'benchmark:run
        {--profile=full : full, capacity, smoke, or fidelity}
        {--renderers= : Comma-separated renderer slugs}
        {--templates= : Comma-separated template slugs}
        {--concurrency= : Capacity profile levels, for example 1,2,4,8,12,16}
        {--iterations= : Renders per capacity level}
        {--seed= : Deterministic scenario ordering seed}
        {--run-id= : Stable run identifier}
        {--resume : Resume using new attempts for incomplete blocks}
        {--allow-dirty : Permit a dirty Git worktree}';

    protected $description = 'Run the reproducible PDF benchmark matrix';

    public function handle(
        BenchmarkMatrix $matrix,
        RunStore $store,
        EnvironmentCollector $environment,
        DockerRuntime $docker,
        FidelityProcessor $fidelity,
        SchemaValidator $schema,
        HostMetadataValidator $metadataValidator,
    ): int {
        $profile = (string) $this->option('profile');
        $seed = (int) ($this->option('seed') ?: config('benchmark.default_seed'));
        $runId = (string) ($this->option('run-id') ?: now('UTC')->format('Ymd-His').'-'.$profile);
        $renderers = $this->csvOption('renderers', array_keys(config('benchmark.renderers')));
        $templateDefaults = in_array($profile, ['full', 'fidelity'], true)
            ? array_keys(config('benchmark.templates'))
            : array_keys(array_filter(config('benchmark.templates'), fn (array $value) => $value['performance']));
        $templates = $this->csvOption('templates', $templateDefaults);
        try {
            $matrixOptions = $this->matrixOptions($profile);
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $this->option('allow-dirty') && $this->gitDirty()) {
            $this->error('The Git worktree is dirty. Commit changes or explicitly use --allow-dirty.');

            return self::FAILURE;
        }

        try {
            $collectedEnvironment = $environment->collect();
            $metadata = $metadataValidator->validate(config('benchmark.required_metadata'), $collectedEnvironment['platform'] ?? null);
            if ($metadata['errors'] !== []) {
                throw new RuntimeException(implode(' ', $metadata['errors']).' Run benchmark:preflight first.');
            }
            foreach ($metadata['warnings'] as $warning) {
                $this->warn($warning);
            }
            $blocks = $matrix->build($profile, $renderers, $templates, $seed, $matrixOptions);
            if ($profile === 'full') {
                $blocks = [...$blocks, ...$matrix->build('fidelity', $renderers, $templates, $seed + 1)];
                usort($blocks, fn (array $a, array $b) => strcmp(
                    hash('sha256', $seed.'|'.BenchmarkMatrix::key($a)),
                    hash('sha256', $seed.'|'.BenchmarkMatrix::key($b)),
                ));
            }
            $runManifest = [
                'schema_version' => 1,
                'run_id' => $runId,
                'profile' => $profile,
                'seed' => $seed,
                'created_at' => now('UTC')->toIso8601String(),
                'renderers' => $renderers,
                'templates' => $templates,
                'matrix_options' => $matrixOptions,
                'retry_policy' => ['application_attempts' => 1, 'renderer_retries' => 0],
                'environment' => $collectedEnvironment,
            ];
            if (($schemaErrors = $schema->manifest($runManifest)) !== []) {
                throw new RuntimeException('Invalid run manifest: '.implode('; ', $schemaErrors));
            }
            $store->initialize($runId, $runManifest, (bool) $this->option('resume'));
            $operations = json_decode((string) file_get_contents(base_path('ops/operations.json')), true, flags: JSON_THROW_ON_ERROR);
            $store->write($runId, 'operations.json', $operations);
        } catch (\InvalidArgumentException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Run {$runId}: ".count($blocks).' deterministic scenario blocks');
        $failedBlocks = 0;

        foreach ($blocks as $index => $block) {
            $key = BenchmarkMatrix::key($block);
            if ($this->option('resume') && $store->hasCompleteBlock($runId, $key)) {
                $this->line('SKIP '.$key.' (complete)');

                continue;
            }

            $attempt = $store->nextAttempt($runId, $key);
            $this->line(sprintf('[%d/%d] %s attempt %d', $index + 1, count($blocks), $key, $attempt));

            if ($block['renderer'] === 'gotenberg' && $block['slug'] === 'first' && ! $docker->restartGotenberg()) {
                $this->warn('Could not perform the controlled Gotenberg restart; block will still record the condition.');
            }

            $result = $this->runBlock($runId, $profile, $block, $attempt, $store, $docker);
            if ($result !== 'complete') {
                $failedBlocks++;
            }

            if ($index < count($blocks) - 1 && ($cooldown = (int) config('benchmark.cooldown_seconds')) > 0) {
                sleep($cooldown);
            }
        }

        if (in_array($profile, ['full', 'fidelity'], true)) {
            $this->line('Inspecting PDFs and generating visual diffs...');
            $fidelity->process($runId);
        }

        $this->newLine();
        $this->info('Raw run data: '.$store->path($runId));

        return $failedBlocks === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<string, mixed> $block */
    private function runBlock(string $runId, string $profile, array $block, int $attempt, RunStore $store, DockerRuntime $docker): string
    {
        $concurrency = min((int) $block['concurrency'], (int) $block['iterations']);
        $processes = [];
        $canonicalArtifact = $store->path($runId, 'pdfs/'.$block['template'].'/'.$block['renderer'].'__'.$block['asset_mode'].'.pdf');
        $artifact = $store->path($runId, "tmp/artifact-{$block['renderer']}-{$block['template']}-{$block['asset_mode']}-{$block['slug']}-a{$attempt}.pdf");
        $barrierDirectory = $store->path($runId, "tmp/barrier-{$block['renderer']}-{$block['template']}-{$block['slug']}-a{$attempt}");
        if (! is_dir($barrierDirectory)) {
            mkdir($barrierDirectory, 0775, true);
        }
        $startPath = $barrierDirectory.'/start';
        $workerObservationPaths = [];

        $store->append($runId, 'raw/blocks.jsonl', [
            'schema_version' => 1,
            'block_key' => BenchmarkMatrix::key($block),
            'attempt' => $attempt,
            'status' => 'partial',
            'expected_observations' => (int) $block['iterations'],
            'actual_observations' => 0,
            'started_at' => now('UTC')->toIso8601String(),
            'block' => $block,
        ]);

        for ($worker = 1; $worker <= $concurrency; $worker++) {
            $iterations = BenchmarkMatrix::workerIterations((int) $block['iterations'], $concurrency, $worker);
            $workerObservationPath = $store->path(
                $runId,
                "raw/attempts/{$block['renderer']}__{$block['variant']}__{$block['template']}__{$block['asset_mode']}__{$block['slug']}/a{$attempt}-w{$worker}.jsonl",
            );
            $workerObservationPaths[] = $workerObservationPath;
            $job = [
                'run_id' => $runId,
                'renderer' => $block['renderer'],
                'variant' => $block['variant'],
                'template' => $block['template'],
                'scenario' => $block['slug'],
                'phase' => $block['phase'],
                'asset_mode' => $block['asset_mode'],
                'attempt' => $attempt,
                'worker' => $worker,
                'iterations' => $iterations,
                'ready_path' => $barrierDirectory."/worker-{$worker}.ready",
                'start_path' => $startPath,
                'artifact_iteration' => 1,
                'artifact_path' => $artifact,
                'observation_path' => $workerObservationPath,
            ];
            $jobPath = $store->path($runId, "tmp/{$block['renderer']}-{$block['template']}-{$block['slug']}-a{$attempt}-w{$worker}.json");
            file_put_contents($jobPath, json_encode($job, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX);

            $process = new Process([PHP_BINARY, base_path('artisan'), 'benchmark:worker', $jobPath], base_path());
            $process->setTimeout(((int) config('benchmark.timeout_seconds') * max(1, count($iterations))) + 30);
            $process->start();
            $processes[] = $process;
        }

        $barrierWaitStartedAt = hrtime(true);
        $barrierDeadline = microtime(true) + 30;
        do {
            $readyWorkers = glob($barrierDirectory.'/*.ready') ?: [];
            $allRunning = array_all($processes, fn (Process $process) => $process->isRunning());
            if (count($readyWorkers) === $concurrency || ! $allRunning) {
                break;
            }
            usleep(10_000);
        } while (microtime(true) < $barrierDeadline);
        $barrierWaitSeconds = (hrtime(true) - $barrierWaitStartedAt) / 1_000_000_000;
        $barrierReadyWorkers = count(glob($barrierDirectory.'/*.ready') ?: []);
        $sampler = new ProcessResourceSampler;
        $rootPids = array_values(array_filter(array_map(fn (Process $process) => $process->getPid(), $processes)));
        $sampler->establishCpuBaseline($rootPids);
        $servicePeak = null;
        $serviceStart = $docker->sampleForRenderer($block['renderer']);
        $startedAt = hrtime(true);
        file_put_contents($startPath, now('UTC')->toIso8601String(), LOCK_EX);
        $lastServiceSample = 0.0;
        $tempPath = storage_path('framework/benchmark');
        $tempPeakBytes = $this->directoryBytes($tempPath);

        do {
            $running = false;
            $pids = [];
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $running = true;
                    $pids[] = $process->getPid();
                }
            }
            $sampler->sample($pids);
            $tempPeakBytes = max($tempPeakBytes, $this->directoryBytes($tempPath));

            if (microtime(true) - $lastServiceSample >= 0.25) {
                $sample = $docker->sampleForRenderer($block['renderer']);
                if ($sample !== null) {
                    $servicePeak ??= $sample;
                    foreach (['memory_bytes', 'pids', 'cpu_seconds_total', 'active_requests', 'queue_size'] as $metric) {
                        if (($sample[$metric] ?? null) !== null) {
                            $servicePeak[$metric] = max($servicePeak[$metric] ?? 0, $sample[$metric]);
                        }
                    }
                    $servicePeak['telemetry_observable'] = ($servicePeak['telemetry_observable'] ?? false)
                        || ($sample['telemetry_observable'] ?? false);
                }
                $lastServiceSample = microtime(true);
            }
            if ($running) {
                usleep(50_000);
            }
        } while ($running);

        $samplerLoopDuration = (hrtime(true) - $startedAt) / 1_000_000_000;
        $observations = [];
        $workerFailure = false;
        foreach ($processes as $index => $process) {
            if (! $process->isSuccessful()) {
                $workerFailure = true;
                $diagnostic = trim($process->getErrorOutput());
                $diagnostic = $diagnostic !== '' ? $diagnostic : trim($process->getOutput());
                $diagnostic = $diagnostic !== '' ? $diagnostic : 'A worker exited unsuccessfully without console output.';
                $store->append($runId, 'raw/worker-failures.jsonl', [
                    'schema_version' => 1,
                    'block_key' => BenchmarkMatrix::key($block),
                    'attempt' => $attempt,
                    'worker' => $index + 1,
                    'exit_code' => $process->getExitCode(),
                    'diagnostic' => mb_substr($diagnostic, 0, 4000),
                    'observed_at' => now('UTC')->toIso8601String(),
                ]);
                $this->warn($diagnostic);
            }
            $workerLines = is_file($workerObservationPaths[$index])
                ? (file($workerObservationPaths[$index], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [])
                : [];
            foreach ($workerLines as $line) {
                if ($line === '') {
                    continue;
                }
                try {
                    $observations[] = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $workerFailure = true;
                }
            }
        }

        usort($observations, fn (array $a, array $b) => $a['iteration'] <=> $b['iteration']);
        $finishedAt = array_column($observations, 'finished_monotonic_ns');
        $duration = $finishedAt === []
            ? $samplerLoopDuration
            : max(0, (max($finishedAt) - $startedAt) / 1_000_000_000);

        $complete = ! $workerFailure
            && $barrierReadyWorkers === $concurrency
            && count($observations) === (int) $block['iterations'];
        $authoritativeArtifact = $block['phase'] === 'fidelity';
        $mayPromoteArtifact = $authoritativeArtifact
            || $profile === 'smoke'
            || ! (bool) config("benchmark.renderers.{$block['renderer']}.core");
        if ($complete && $mayPromoteArtifact && is_file($artifact)) {
            $canonicalDirectory = dirname($canonicalArtifact);
            if (! is_dir($canonicalDirectory)) {
                mkdir($canonicalDirectory, 0775, true);
            }
            if (! rename($artifact, $canonicalArtifact)) {
                $complete = false;
            }
        } elseif ($authoritativeArtifact && is_file($canonicalArtifact)) {
            unlink($canonicalArtifact);
        }
        $status = $complete ? 'complete' : 'partial';
        $resource = [
            'schema_version' => 1,
            'block_key' => BenchmarkMatrix::key($block),
            'attempt' => $attempt,
            'application' => $sampler->result(),
            'render_service' => $servicePeak === null ? null : [
                ...$servicePeak,
                'cpu_seconds_delta' => max(0, ($servicePeak['cpu_seconds_total'] ?? 0) - ($serviceStart['cpu_seconds_total'] ?? 0)),
            ],
            'external_provider' => config("benchmark.renderers.{$block['renderer']}.external")
                ? ['resources' => null, 'reason' => 'Provider-side resource consumption is not observable.']
                : null,
            'storage' => [
                'benchmark_temp_peak_bytes' => $tempPeakBytes,
                'run_artifacts_bytes_after_block' => $this->directoryBytes($store->path($runId)),
            ],
            'worker_barrier' => [
                'expected_workers' => $concurrency,
                'ready_workers' => $barrierReadyWorkers,
                'wait_seconds' => round($barrierWaitSeconds, 6),
            ],
            'load' => [
                'offered_concurrency' => $concurrency,
                'planned_iterations' => (int) $block['iterations'],
                'application_queue' => [
                    'depth' => null,
                    'reason' => 'The harness uses a fixed in-process worker pool and no application queue.',
                ],
                'provider_queue' => config("benchmark.renderers.{$block['renderer']}.external")
                    ? ['depth' => null, 'reason' => 'Provider-side queue depth is not observable.']
                    : null,
            ],
        ];
        $store->append($runId, 'resources/resources.jsonl', $resource);
        $store->append($runId, 'raw/blocks.jsonl', [
            'schema_version' => 1,
            'block_key' => BenchmarkMatrix::key($block),
            'attempt' => $attempt,
            'status' => $status,
            'expected_observations' => (int) $block['iterations'],
            'actual_observations' => count($observations),
            'duration_seconds' => round($duration, 6),
            'sampler_loop_seconds' => round($samplerLoopDuration, 6),
            'cooldown_seconds' => (int) config('benchmark.cooldown_seconds'),
            'worker_barrier_ready' => $barrierReadyWorkers === $concurrency,
            'completed_at' => now('UTC')->toIso8601String(),
            'block' => $block,
        ]);

        return $status;
    }

    private function directoryBytes(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }

        $bytes = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $bytes += $file->getSize();
                }
            }
        } catch (\UnexpectedValueException) {
            // Renderer temporary directories may disappear between discovery and traversal.
        }

        return $bytes;
    }

    private function gitDirty(): bool
    {
        exec('git status --porcelain 2>/dev/null', $output, $exit);

        return $exit !== 0 || $output !== [];
    }

    /**
     * @param  list<string>  $default
     * @return list<string>
     */
    private function csvOption(string $name, array $default): array
    {
        $value = $this->option($name);

        return $value ? array_values(array_filter(array_map('trim', explode(',', $value)))) : $default;
    }

    /** @return array{concurrency_levels?: list<int>, iterations?: int} */
    private function matrixOptions(string $profile): array
    {
        $concurrencyOption = $this->option('concurrency');
        $iterationsOption = $this->option('iterations');
        if ($profile !== 'capacity') {
            if ($concurrencyOption !== null || $iterationsOption !== null) {
                throw new \InvalidArgumentException('--concurrency and --iterations may only be used with --profile=capacity.');
            }

            return [];
        }

        $levels = $concurrencyOption === null
            ? config('benchmark.capacity.concurrency_levels')
            : array_map(function (string $value): int {
                if (preg_match('/^[1-9]\d*$/', trim($value)) !== 1) {
                    throw new \InvalidArgumentException('Capacity concurrency levels must be positive integers.');
                }

                return (int) trim($value);
            }, explode(',', (string) $concurrencyOption));
        sort($levels, SORT_NUMERIC);

        if ($iterationsOption !== null && preg_match('/^[1-9]\d*$/', (string) $iterationsOption) !== 1) {
            throw new \InvalidArgumentException('Capacity iterations must be a positive integer.');
        }

        return [
            'concurrency_levels' => $levels,
            'iterations' => $iterationsOption === null ? (int) config('benchmark.capacity.iterations') : (int) $iterationsOption,
        ];
    }
}
