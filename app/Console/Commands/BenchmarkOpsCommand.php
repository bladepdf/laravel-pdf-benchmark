<?php

namespace App\Console\Commands;

use App\Benchmark\EnvironmentCollector;
use Illuminate\Console\Command;

final class BenchmarkOpsCommand extends Command
{
    protected $signature = 'benchmark:ops {--measure : Add observable binary and image measurements}';

    protected $description = 'Generate the operational comparison manifest';

    public function handle(EnvironmentCollector $environment): int
    {
        $manifest = json_decode((string) file_get_contents(base_path('ops/operations.json')), true, flags: JSON_THROW_ON_ERROR);
        if ($this->option('measure')) {
            $manifest['measured_at'] = now('UTC')->toIso8601String();
            $manifest['measurement'] = $environment->collect();
            $manifest['measurement_note'] = 'For a clean-cache image build, use npm run ops-install from the host.';
        }
        $target = config('benchmark.paths.work').'/operations.json';
        file_put_contents($target, json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL, LOCK_EX);
        $this->info("Operational manifest written to {$target}");

        return self::SUCCESS;
    }
}
