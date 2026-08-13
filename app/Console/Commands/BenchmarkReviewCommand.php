<?php

namespace App\Console\Commands;

use App\Benchmark\RunStore;
use Illuminate\Console\Command;

final class BenchmarkReviewCommand extends Command
{
    protected $signature = 'benchmark:review
        {run : Run ID}
        {--host=127.0.0.1 : Bind address}
        {--port=8000 : Bind port}';

    protected $description = 'Start a loopback-only fidelity review UI';

    public function handle(RunStore $store): int
    {
        $run = (string) $this->argument('run');
        if (! is_file($store->path($run, 'fidelity-review.json'))) {
            $this->error("Run {$run} has no fidelity review manifest.");

            return self::FAILURE;
        }

        putenv('BENCHMARK_REVIEW_ENABLED=1');
        $_ENV['BENCHMARK_REVIEW_ENABLED'] = '1';
        $_SERVER['BENCHMARK_REVIEW_ENABLED'] = '1';
        config(['benchmark.review_enabled' => true]);
        $urlHost = $this->option('host') === '0.0.0.0' ? '127.0.0.1' : $this->option('host');
        $this->info("Review {$run} at http://{$urlHost}:{$this->option('port')}/benchmark-review/{$run}");

        return $this->call('serve', [
            '--host' => $this->option('host'),
            '--port' => $this->option('port'),
            '--no-reload' => true,
        ]);
    }
}
