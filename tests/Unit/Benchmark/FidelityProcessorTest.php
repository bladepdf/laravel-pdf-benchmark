<?php

namespace Tests\Unit\Benchmark;

use App\Benchmark\FidelityProcessor;
use App\Benchmark\RunStore;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

final class FidelityProcessorTest extends TestCase
{
    private string $runsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runsPath = sys_get_temp_dir().'/laravel-pdf-fidelity-'.bin2hex(random_bytes(8));
        config()->set('benchmark.paths.runs', $this->runsPath);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->runsPath);

        parent::tearDown();
    }

    public function test_it_creates_a_stable_diff_manifest_and_review_entries_for_missing_artifacts(): void
    {
        $runId = 'diff-manifest';
        app(RunStore::class)->initialize($runId, [
            'schema_version' => 1,
            'run_id' => $runId,
            'profile' => 'fidelity',
            'seed' => 1,
            'created_at' => '2026-08-09T00:00:00Z',
            'renderers' => ['dompdf', 'browsershot'],
            'templates' => ['modern-invoice'],
            'environment' => [],
        ], false);

        $manifest = app(FidelityProcessor::class)->process($runId);

        $this->assertSame(1, $manifest['schema_version']);
        $this->assertSame('browsershot', $manifest['reference_renderer']);
        $this->assertSame(
            ['browsershot__modern-invoice__portable', 'dompdf__modern-invoice__portable'],
            array_keys($manifest['documents']),
        );
        $this->assertTrue($manifest['documents']['browsershot__modern-invoice__portable']['missing_pdf']);
        $this->assertTrue($manifest['documents']['dompdf__modern-invoice__portable']['missing_pdf']);

        $review = json_decode(
            (string) file_get_contents($this->runsPath."/{$runId}/fidelity-review.json"),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertCount(8, $review['entries']);
        $this->assertSame(
            'browsershot__modern-invoice__portable__gradient-flexbox',
            $review['entries'][0]['key'],
        );
        $this->assertNull($review['entries'][0]['status']);
    }
}
