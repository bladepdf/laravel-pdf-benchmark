<?php

namespace Tests\Unit\Benchmark;

use App\Benchmark\RunStore;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class RunStoreTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing-benchmark-runs');
        File::deleteDirectory($this->root);
        config(['benchmark.paths.runs' => $this->root]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_resume_uses_only_complete_blocks_and_increments_attempts(): void
    {
        $store = app(RunStore::class);
        $store->initialize('test-run', ['schema_version' => 1], false);
        $store->append('test-run', 'raw/blocks.jsonl', ['block_key' => 'a', 'attempt' => 1, 'status' => 'partial']);

        $this->assertFalse($store->hasCompleteBlock('test-run', 'a'));
        $this->assertSame(2, $store->nextAttempt('test-run', 'a'));

        $store->append('test-run', 'raw/blocks.jsonl', ['block_key' => 'a', 'attempt' => 2, 'status' => 'complete']);
        $this->assertTrue($store->hasCompleteBlock('test-run', 'a'));
        $this->assertSame(2, $store->latestCompleteBlocks('test-run')['a']['attempt']);
    }

    public function test_resume_rejects_a_changed_matrix_or_environment(): void
    {
        $store = app(RunStore::class);
        $store->initialize('resume-run', [
            'schema_version' => 1,
            'profile' => 'full',
            'seed' => 1,
            'renderers' => ['dompdf'],
            'templates' => ['simple-invoice'],
            'retry_policy' => ['application_attempts' => 1],
            'environment' => ['collected_at' => 'first', 'git' => ['sha' => 'abc']],
        ], false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resume metadata does not match');

        $store->initialize('resume-run', [
            'schema_version' => 1,
            'profile' => 'full',
            'seed' => 2,
            'renderers' => ['dompdf'],
            'templates' => ['simple-invoice'],
            'retry_policy' => ['application_attempts' => 1],
            'environment' => ['collected_at' => 'second', 'git' => ['sha' => 'abc']],
        ], true);
    }

    public function test_resume_rejects_changed_capacity_levels(): void
    {
        $store = app(RunStore::class);
        $manifest = [
            'schema_version' => 1,
            'profile' => 'capacity',
            'seed' => 1,
            'renderers' => ['dompdf'],
            'templates' => ['simple-invoice'],
            'matrix_options' => ['concurrency_levels' => [1, 2, 4], 'iterations' => 100],
            'retry_policy' => ['application_attempts' => 1],
            'environment' => ['collected_at' => 'first', 'git' => ['sha' => 'abc']],
        ];
        $store->initialize('capacity-resume', $manifest, false);
        $manifest['matrix_options']['concurrency_levels'] = [1, 2, 4, 8];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resume metadata does not match');

        $store->initialize('capacity-resume', $manifest, true);
    }
}
