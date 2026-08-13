<?php

namespace Tests\Unit\Benchmark;

use App\Benchmark\PrometheusMetrics;
use PHPUnit\Framework\TestCase;

final class PrometheusMetricsTest extends TestCase
{
    public function test_it_reads_labelled_gotenberg_otel_gauges(): void
    {
        $payload = <<<'METRICS'
            # TYPE chromium_requests_active gauge
            chromium_requests_active{otel_scope_version="8.34.0"} 2
            # TYPE chromium_requests_queue_size gauge
            chromium_requests_queue_size{otel_scope_version="8.34.0"} 3
            METRICS;

        $metrics = new PrometheusMetrics;

        $this->assertSame(2, $metrics->integerGauge($payload, 'chromium_requests_active'));
        $this->assertSame(3, $metrics->integerGauge($payload, 'chromium_requests_queue_size'));
        $this->assertNull($metrics->integerGauge($payload, 'missing_metric'));
    }
}
