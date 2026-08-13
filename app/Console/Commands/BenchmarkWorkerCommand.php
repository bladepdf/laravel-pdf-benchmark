<?php

namespace App\Console\Commands;

use App\Benchmark\PdfRenderer;
use App\Benchmark\RunStore;
use App\Benchmark\SchemaValidator;
use Illuminate\Console\Command;

final class BenchmarkWorkerCommand extends Command
{
    protected $signature = 'benchmark:worker {job : Absolute JSON job file path}';

    protected $description = 'Internal benchmark worker (not part of the public workflow)';

    protected $hidden = true;

    public function handle(PdfRenderer $renderer, SchemaValidator $schema, RunStore $store): int
    {
        $job = json_decode((string) file_get_contents($this->argument('job')), true, flags: JSON_THROW_ON_ERROR);
        file_put_contents($job['ready_path'], (string) getmypid(), LOCK_EX);
        $barrierDeadline = microtime(true) + 35;
        while (! is_file($job['start_path']) && microtime(true) < $barrierDeadline) {
            usleep(5_000);
        }
        if (! is_file($job['start_path'])) {
            throw new \RuntimeException('Worker start barrier timed out.');
        }

        foreach ($job['iterations'] as $iteration) {
            $result = $renderer->render($job['renderer'], $job['template'], $job['asset_mode']);
            $pdfBase64 = $result['pdf_base64'];
            unset($result['pdf_base64']);

            if ($pdfBase64 !== null && $iteration === $job['artifact_iteration']) {
                $directory = dirname($job['artifact_path']);
                if (! is_dir($directory)) {
                    mkdir($directory, 0775, true);
                }
                file_put_contents($job['artifact_path'], base64_decode($pdfBase64, true), LOCK_EX);
            }

            $observation = [
                'schema_version' => 1,
                'run_id' => $job['run_id'],
                'renderer' => $job['renderer'],
                'variant' => $job['variant'],
                'template' => $job['template'],
                'scenario' => $job['scenario'],
                'phase' => $job['phase'],
                'iteration' => $iteration,
                'attempt' => $job['attempt'],
                'worker' => $job['worker'],
                'asset_mode' => $job['asset_mode'],
                'observed_at' => now('UTC')->toIso8601String(),
                ...$result,
            ];

            if (($errors = $schema->observation($observation)) !== []) {
                throw new \RuntimeException('Invalid observation: '.implode('; ', $errors));
            }

            $line = json_encode($observation, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;
            $directory = dirname($job['observation_path']);
            if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new \RuntimeException('Could not create the worker observation directory.');
            }
            if (file_put_contents($job['observation_path'], $line, FILE_APPEND | LOCK_EX) === false) {
                throw new \RuntimeException('Could not persist worker observation.');
            }
            $store->append($job['run_id'], 'raw/observations.jsonl', $observation);
        }

        return self::SUCCESS;
    }
}
