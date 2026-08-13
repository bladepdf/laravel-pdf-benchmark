<?php

namespace Tests\Unit\Benchmark;

use App\Benchmark\Statistics;
use PHPUnit\Framework\TestCase;

final class StatisticsTest extends TestCase
{
    public function test_it_uses_nearest_rank_percentiles(): void
    {
        $values = range(1, 100);

        $this->assertSame(50.0, Statistics::nearestRank($values, 50));
        $this->assertSame(95.0, Statistics::nearestRank($values, 95));
        $this->assertSame(99.0, Statistics::nearestRank($values, 99));
        $this->assertNull(Statistics::nearestRank([], 95));
    }

    public function test_it_reports_attempted_and_successful_throughput_separately(): void
    {
        $summary = Statistics::summarize([
            ['status' => 'success', 'wall_ms' => 10, 'pdf_bytes' => 100],
            ['status' => 'failure', 'wall_ms' => 20, 'pdf_bytes' => null],
            ['status' => 'timeout', 'wall_ms' => 30, 'pdf_bytes' => null],
        ], 2.0);

        $this->assertSame(1.5, $summary['attempted_throughput']);
        $this->assertSame(0.5, $summary['successful_throughput']);
        $this->assertSame(1, $summary['failures']);
        $this->assertSame(1, $summary['timeouts']);
    }

    public function test_it_reports_latency_variability_and_observed_in_flight_work(): void
    {
        $summary = Statistics::summarize([
            ['status' => 'success', 'wall_ms' => 1000, 'pdf_bytes' => 100, 'started_monotonic_ns' => 0, 'finished_monotonic_ns' => 1_000_000_000],
            ['status' => 'success', 'wall_ms' => 2000, 'pdf_bytes' => 100, 'started_monotonic_ns' => 500_000_000, 'finished_monotonic_ns' => 2_500_000_000],
        ], 3.0, 2);

        $this->assertSame(1500.0, $summary['mean_ms']);
        $this->assertEqualsWithDelta(707.106, $summary['stddev_ms'], .001);
        $this->assertEqualsWithDelta(47.14, $summary['cv_pct'], .01);
        $this->assertSame(2, $summary['peak_in_flight']);
        $this->assertSame(1.0, $summary['average_in_flight']);
        $this->assertSame(50.0, $summary['worker_utilization_pct']);
    }
}
