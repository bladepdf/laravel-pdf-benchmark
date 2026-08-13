<?php

namespace Tests\Unit\Benchmark;

use App\Benchmark\BenchmarkMatrix;
use Tests\TestCase;

final class BenchmarkMatrixTest extends TestCase
{
    public function test_worker_scheduling_is_balanced_and_complete(): void
    {
        $assignments = [];
        for ($worker = 1; $worker <= 5; $worker++) {
            $assignments[] = BenchmarkMatrix::workerIterations(103, 5, $worker);
        }

        $flattened = array_merge(...$assignments);
        sort($flattened);
        $this->assertSame(range(1, 103), $flattened);
        $this->assertLessThanOrEqual(1, max(array_map('count', $assignments)) - min(array_map('count', $assignments)));
    }

    public function test_order_is_deterministic_for_a_seed(): void
    {
        $matrix = app(BenchmarkMatrix::class);
        $first = $matrix->build('smoke', ['dompdf', 'browsershot'], ['simple-invoice'], 42);
        $second = $matrix->build('smoke', ['dompdf', 'browsershot'], ['simple-invoice'], 42);

        $this->assertSame($first, $second);
        $this->assertCount(4, $first);
    }

    public function test_asset_fidelity_has_native_and_remediated_blocks(): void
    {
        $blocks = app(BenchmarkMatrix::class)->build('fidelity', ['dompdf'], ['local-assets'], 1);

        $this->assertSame(['documented-remediation', 'native-path'], collect($blocks)->pluck('asset_mode')->sort()->values()->all());
    }

    public function test_persistent_browsershot_is_performance_only(): void
    {
        $this->assertSame([], app(BenchmarkMatrix::class)->build(
            'fidelity',
            ['browsershot-persistent'],
            ['simple-invoice'],
            1,
        ));
    }

    public function test_capacity_profile_builds_the_requested_concurrency_sweep(): void
    {
        $blocks = app(BenchmarkMatrix::class)->build(
            'capacity',
            ['browsershot'],
            ['simple-invoice'],
            42,
            ['concurrency_levels' => [1, 2, 4, 8], 'iterations' => 25],
        );

        $this->assertCount(6, $blocks);
        $measured = collect($blocks)->where('phase', 'measured')->sortBy('concurrency')->values();
        $this->assertSame([1, 2, 4, 8], $measured->pluck('concurrency')->all());
        $this->assertSame([25, 25, 25, 25], $measured->pluck('iterations')->all());
    }

    public function test_capacity_profile_rejects_duplicate_or_unsafe_levels(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(BenchmarkMatrix::class)->build(
            'capacity',
            ['dompdf'],
            ['simple-invoice'],
            1,
            ['concurrency_levels' => [1, 1], 'iterations' => 10],
        );
    }
}
