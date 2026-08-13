<?php

namespace App\Console\Commands;

use App\Benchmark\ReportGenerator;
use App\Benchmark\RunStore;
use Illuminate\Console\Command;
use Throwable;

final class BenchmarkReportCommand extends Command
{
    protected $signature = 'benchmark:report
        {run : Run ID}
        {--costs= : Path to a local, uncommitted pricing snapshot}
        {--allow-unreviewed : Development-only override for draft reports}';

    protected $description = 'Generate summary JSON, long-form CSV, costs, and Markdown report from complete attempts';

    public function handle(ReportGenerator $generator, RunStore $store): int
    {
        try {
            $generator->generate(
                (string) $this->argument('run'),
                (bool) $this->option('allow-unreviewed'),
                $this->option('costs') === null ? null : (string) $this->option('costs'),
            );
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('Generated '.$store->path((string) $this->argument('run'), 'REPORT.md'));

        return self::SUCCESS;
    }
}
