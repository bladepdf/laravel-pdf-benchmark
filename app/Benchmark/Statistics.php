<?php

namespace App\Benchmark;

final class Statistics
{
    /** @param list<float|int> $values */
    public static function nearestRank(array $values, float $percentile): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $rank = max(1, (int) ceil(($percentile / 100) * count($values)));

        return (float) $values[$rank - 1];
    }

    /**
     * @param  list<array<string, mixed>>  $observations
     * @return array<string, int|float|null>
     */
    public static function summarize(array $observations, float $blockDurationSeconds, int $configuredConcurrency = 1): array
    {
        $successes = array_values(array_filter($observations, fn (array $row) => ($row['status'] ?? null) === 'success'));
        $latencies = array_map(fn (array $row) => (float) $row['wall_ms'], $successes);
        $attempted = count($observations);
        $successful = count($successes);
        $mean = $latencies === [] ? null : array_sum($latencies) / count($latencies);
        $standardDeviation = self::sampleStandardDeviation($latencies, $mean);
        $busySeconds = array_sum(array_map(fn (array $row) => (float) ($row['wall_ms'] ?? 0), $observations)) / 1000;
        $averageInFlight = $blockDurationSeconds > 0 ? $busySeconds / $blockDurationSeconds : null;

        return [
            'attempted' => $attempted,
            'successful' => $successful,
            'failures' => count(array_filter($observations, fn (array $row) => ($row['status'] ?? null) === 'failure')),
            'timeouts' => count(array_filter($observations, fn (array $row) => ($row['status'] ?? null) === 'timeout')),
            'p50_ms' => self::nearestRank($latencies, 50),
            'p95_ms' => self::nearestRank($latencies, 95),
            'p99_ms' => self::nearestRank($latencies, 99),
            'mean_ms' => $mean,
            'stddev_ms' => $standardDeviation,
            'cv_pct' => $mean !== null && $mean > 0 && $standardDeviation !== null ? ($standardDeviation / $mean) * 100 : null,
            'mean_pdf_bytes' => $successful > 0 ? array_sum(array_column($successes, 'pdf_bytes')) / $successful : null,
            'attempted_throughput' => $blockDurationSeconds > 0 ? $attempted / $blockDurationSeconds : null,
            'successful_throughput' => $blockDurationSeconds > 0 ? $successful / $blockDurationSeconds : null,
            'configured_concurrency' => $configuredConcurrency,
            'peak_in_flight' => self::peakInFlight($observations),
            'average_in_flight' => $averageInFlight,
            'worker_utilization_pct' => $averageInFlight === null ? null : ($averageInFlight / $configuredConcurrency) * 100,
        ];
    }

    /** @param list<float|int> $values */
    private static function sampleStandardDeviation(array $values, ?float $mean): ?float
    {
        if (count($values) < 2 || $mean === null) {
            return null;
        }

        $squaredDifferences = array_map(fn (float|int $value) => (((float) $value) - $mean) ** 2, $values);

        return sqrt(array_sum($squaredDifferences) / (count($values) - 1));
    }

    /** @param list<array<string, mixed>> $observations */
    private static function peakInFlight(array $observations): int
    {
        $events = [];
        foreach ($observations as $row) {
            if (! isset($row['started_monotonic_ns'], $row['finished_monotonic_ns'])) {
                continue;
            }
            $events[] = [(int) $row['started_monotonic_ns'], 1];
            $events[] = [(int) $row['finished_monotonic_ns'], -1];
        }
        usort($events, fn (array $left, array $right) => [$left[0], $left[1]] <=> [$right[0], $right[1]]);

        $current = 0;
        $peak = 0;
        foreach ($events as [, $delta]) {
            $current += $delta;
            $peak = max($peak, $current);
        }

        return $peak;
    }
}
