<?php

namespace Tests\Unit\Benchmark;

use App\Benchmark\SchemaValidator;
use PHPUnit\Framework\TestCase;

final class SchemaValidatorTest extends TestCase
{
    public function test_it_validates_stable_observation_identifiers(): void
    {
        $observation = [
            'schema_version' => 1, 'run_id' => 'eu-2026', 'renderer' => 'dompdf', 'variant' => 'default',
            'template' => 'simple-invoice', 'asset_mode' => 'portable', 'scenario' => 'sequential', 'phase' => 'measured',
            'iteration' => 1, 'attempt' => 1, 'worker' => 1, 'status' => 'success', 'wall_ms' => 10.0,
            'started_monotonic_ns' => 100, 'finished_monotonic_ns' => 200, 'request_bytes' => 0, 'response_bytes' => 0,
        ];

        $this->assertSame([], (new SchemaValidator)->observation($observation));
        $observation['run_id'] = '../unsafe';
        $this->assertContains('Invalid run_id', (new SchemaValidator)->observation($observation));
        $observation['renderer'] = 'DOMPDF unsafe';
        $this->assertContains('Invalid renderer', (new SchemaValidator)->observation($observation));
    }

    public function test_it_validates_run_manifests(): void
    {
        $manifest = [
            'schema_version' => 1,
            'run_id' => 'eu-2026',
            'profile' => 'full',
            'seed' => 123,
            'created_at' => '2026-08-09T00:00:00Z',
            'renderers' => ['dompdf'],
            'templates' => ['simple-invoice'],
            'environment' => [],
        ];

        $this->assertSame([], (new SchemaValidator)->manifest($manifest));
        $manifest['profile'] = 'unknown';
        $this->assertContains('Invalid profile', (new SchemaValidator)->manifest($manifest));
    }

    public function test_capacity_manifest_requires_matrix_options(): void
    {
        $manifest = [
            'schema_version' => 1,
            'run_id' => 'capacity-run',
            'profile' => 'capacity',
            'seed' => 123,
            'created_at' => '2026-08-13T00:00:00Z',
            'renderers' => ['dompdf'],
            'templates' => ['simple-invoice'],
            'environment' => [],
        ];

        $this->assertContains('Invalid capacity matrix_options', (new SchemaValidator)->manifest($manifest));
        $manifest['matrix_options'] = ['concurrency_levels' => [1, 2, 4, 8], 'iterations' => 100];
        $this->assertSame([], (new SchemaValidator)->manifest($manifest));
    }
}
