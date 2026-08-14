<?php

namespace Tests\Unit\Benchmark;

use App\Benchmark\BenchmarkMatrix;
use App\Benchmark\ReportGenerator;
use App\Benchmark\RunStore;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

final class ReportGeneratorTest extends TestCase
{
    private string $runsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runsPath = sys_get_temp_dir().'/laravel-pdf-report-'.bin2hex(random_bytes(8));
        config()->set('benchmark.paths.runs', $this->runsPath);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->runsPath);

        parent::tearDown();
    }

    public function test_capacity_report_contains_saturation_and_resource_metrics(): void
    {
        $runId = 'capacity-report';
        $options = ['concurrency_levels' => [1, 2], 'iterations' => 2];
        $store = app(RunStore::class);
        $store->initialize($runId, [
            'schema_version' => 1,
            'run_id' => $runId,
            'profile' => 'capacity',
            'seed' => 42,
            'created_at' => '2026-08-13T00:00:00Z',
            'renderers' => ['dompdf'],
            'templates' => ['simple-invoice'],
            'matrix_options' => $options,
            'environment' => [
                'declared' => [
                    'host_label' => 'budget-test',
                    'region' => 'eu-test',
                    'host_provider' => 'example',
                    'host_instance_type' => 'shared-2',
                    'host_cpu_allocation' => 'shared',
                    'host_purpose' => 'budget',
                    'host_vcpu' => 2,
                    'host_memory_mib' => 4096,
                    'cloudflare_plan' => 'not-run',
                    'bladepdf_plan' => 'not-run',
                    'bladepdf_concurrency' => 1,
                ],
                'platform' => ['cpu_logical' => 2],
                'git' => ['sha' => 'abc123', 'dirty' => false],
            ],
        ], false);

        foreach (app(BenchmarkMatrix::class)->build('capacity', ['dompdf'], ['simple-invoice'], 42, $options) as $block) {
            $key = BenchmarkMatrix::key($block);
            $store->append($runId, 'raw/blocks.jsonl', [
                'block_key' => $key,
                'attempt' => 1,
                'status' => 'complete',
                'duration_seconds' => 1.0,
                'block' => $block,
            ]);
            for ($iteration = 1; $iteration <= $block['iterations']; $iteration++) {
                $start = ($iteration - 1) * 100_000_000;
                $store->append($runId, 'raw/observations.jsonl', [
                    'renderer' => 'dompdf',
                    'variant' => 'default',
                    'template' => 'simple-invoice',
                    'asset_mode' => 'portable',
                    'scenario' => $block['slug'],
                    'attempt' => 1,
                    'iteration' => $iteration,
                    'status' => 'success',
                    'wall_ms' => 100,
                    'pdf_bytes' => 1000,
                    'started_monotonic_ns' => $start,
                    'finished_monotonic_ns' => $start + 100_000_000,
                    'response_headers' => [],
                ]);
            }
            $store->append($runId, 'resources/resources.jsonl', [
                'block_key' => $key,
                'attempt' => 1,
                'application' => ['aggregate_rss_bytes' => 1024, 'cpu_seconds_total' => .5],
                'render_service' => null,
            ]);
        }

        $summary = app(ReportGenerator::class)->generate($runId);

        $this->assertCount(4, $summary['results']);
        $this->assertSame(0, $summary['methodology']['cloudflare_quick_actions_cache_ttl_seconds']);
        $capacityTwo = collect($summary['results'])->firstWhere('scenario', 'concurrency-2');
        $this->assertSame(2, $capacityTwo['configured_concurrency']);
        $this->assertSame(25.0, $capacityTwo['resources']['application']['cpu_utilization_pct']);
        $this->assertStringContainsString('Capacity sweep observations', (string) file_get_contents($this->runsPath."/{$runId}/REPORT.md"));
        $this->assertStringContainsString('Cloudflare Quick Actions cache disabled with `cacheTTL=0`', (string) file_get_contents($this->runsPath."/{$runId}/REPORT.md"));

        $lines = file($this->runsPath."/{$runId}/summary.csv", FILE_IGNORE_NEW_LINES);
        $this->assertNotFalse($lines);
        $this->assertSame(count(str_getcsv($lines[0])), count(str_getcsv($lines[1])));
    }
}
