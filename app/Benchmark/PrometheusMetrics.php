<?php

namespace App\Benchmark;

final class PrometheusMetrics
{
    public function integerGauge(string $payload, string $name): ?int
    {
        if (preg_match('/^'.preg_quote($name, '/').'(?:\{[^}]*\})?\s+([0-9.eE+-]+)$/m', $payload, $match) !== 1) {
            return null;
        }

        return (int) ceil((float) $match[1]);
    }
}
