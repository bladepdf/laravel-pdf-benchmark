<?php

namespace App\Console\Commands;

use App\Benchmark\EnvironmentCollector;
use App\Benchmark\HostMetadataValidator;
use Illuminate\Console\Command;

final class BenchmarkPreflightCommand extends Command
{
    protected $signature = 'benchmark:preflight
        {--renderers= : Comma-separated renderer slugs}
        {--allow-incomplete : Permit missing run metadata or paid credentials for development}';

    protected $description = 'Validate and record the benchmark environment without exposing host identity or secrets';

    public function handle(EnvironmentCollector $collector, HostMetadataValidator $metadataValidator): int
    {
        $renderers = $this->csvOption('renderers', array_keys(config('benchmark.renderers')));
        $errors = [];
        $warnings = [];

        foreach ($renderers as $renderer) {
            if (config("benchmark.renderers.{$renderer}") === null) {
                $errors[] = "Unknown renderer: {$renderer}";
            }
        }

        foreach (['qpdf', 'pdfinfo', 'pdffonts', 'pdftotext', 'pdftoppm', 'node'] as $binary) {
            if (! $this->binaryExists($binary)) {
                $errors[] = "Missing required binary: {$binary}";
            }
        }

        if (in_array('cloudflare', $renderers, true)) {
            foreach (['CLOUDFLARE_ACCOUNT_ID', 'CLOUDFLARE_API_TOKEN'] as $name) {
                $value = $name === 'CLOUDFLARE_ACCOUNT_ID'
                    ? config('laravel-pdf.cloudflare.account_id')
                    : config('laravel-pdf.cloudflare.api_token');
                if (! $value) {
                    $errors[] = "Missing credential: {$name}";
                }
            }
        }

        if (in_array('bladepdf', $renderers, true) && ! config('bladepdf.api_key')) {
            $errors[] = 'Missing credential: BLADEPDF_API_KEY';
        }

        if (in_array('gotenberg', $renderers, true) && ! $this->httpReady(rtrim((string) config('laravel-pdf.gotenberg.url'), '/').'/health')) {
            $errors[] = 'Gotenberg health endpoint is unavailable.';
        }
        if (in_array('gotenberg', $renderers, true) && ! $this->gotenbergMetricsReady()) {
            $errors[] = 'Gotenberg OpenTelemetry active-request and queue metrics are unavailable.';
        }

        if (in_array('browsershot-persistent', $renderers, true) && ! $this->httpReady(rtrim((string) config('benchmark.persistent_chromium_url'), '/').'/json/version')) {
            $errors[] = 'Persistent Chromium endpoint is unavailable.';
        }

        if (php_uname('s') !== 'Linux' || php_uname('m') !== 'x86_64') {
            $warnings[] = 'This is a development/smoke environment; publishable runs require Linux x86_64.';
        }

        $environment = $collector->collect();
        $metadata = $metadataValidator->validate(config('benchmark.required_metadata'), $environment['platform'] ?? null);
        $errors = [...$errors, ...$metadata['errors']];
        $warnings = [...$warnings, ...$metadata['warnings']];
        if (($environment['git']['sha'] ?? '') === '') {
            $errors[] = 'Git HEAD commit is unavailable.';
        }
        if (($environment['git']['dirty'] ?? true) === true) {
            $errors[] = 'The Git worktree is dirty.';
        }

        $errors = array_values(array_unique($errors));
        $warnings = array_values(array_unique($warnings));

        $payload = [
            'schema_version' => 1,
            'status' => $errors === [] ? 'ready' : ($this->option('allow-incomplete') ? 'incomplete-allowed' : 'failed'),
            'selected_renderers' => $renderers,
            'errors' => $errors,
            'warnings' => $warnings,
            'environment' => $environment,
        ];

        $path = config('benchmark.paths.work').'/preflight.json';
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL, LOCK_EX);

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }
        foreach ($errors as $error) {
            $this->error($error);
        }
        $this->info("Preflight manifest written to {$path}");

        return $errors === [] || $this->option('allow-incomplete') ? self::SUCCESS : self::FAILURE;
    }

    private function binaryExists(string $binary): bool
    {
        $output = [];
        exec('command -v '.escapeshellarg($binary), $output, $exit);

        return $exit === 0;
    }

    private function httpReady(string $url): bool
    {
        $context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);

        return @file_get_contents($url, false, $context) !== false;
    }

    private function gotenbergMetricsReady(): bool
    {
        $context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
        $metrics = @file_get_contents((string) config('benchmark.gotenberg_metrics_url'), false, $context);

        return is_string($metrics)
            && str_contains($metrics, 'chromium_requests_active')
            && str_contains($metrics, 'chromium_requests_queue_size');
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
}
