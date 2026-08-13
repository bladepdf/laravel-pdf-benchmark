<?php

namespace Tests\Unit\Benchmark;

use App\Benchmark\HostMetadataValidator;
use PHPUnit\Framework\TestCase;

final class HostMetadataValidatorTest extends TestCase
{
    public function test_it_validates_declared_host_shape_against_observed_resources(): void
    {
        $result = (new HostMetadataValidator)->validate($this->metadata(), [
            'cpu_logical' => 8,
            'memory_bytes' => 16 * 1024 * 1024 * 1024,
        ]);

        $this->assertSame([], $result['errors']);
        $this->assertCount(1, $result['warnings']);
    }

    public function test_it_rejects_resource_mismatch_and_identifying_host_labels(): void
    {
        $metadata = $this->metadata();
        $metadata['host_label'] = '192.0.2.10';
        $result = (new HostMetadataValidator)->validate($metadata, [
            'cpu_logical' => 4,
            'memory_bytes' => 8 * 1024 * 1024 * 1024,
        ]);

        $this->assertCount(3, $result['errors']);
        $this->assertStringContainsString('anonymous label', implode(' ', $result['errors']));
        $this->assertStringContainsString('does not match', implode(' ', $result['errors']));
        $this->assertStringContainsString('differs from detected memory', implode(' ', $result['errors']));
    }

    /** @return array<string, mixed> */
    private function metadata(): array
    {
        return [
            'host_label' => 'budget-8vcpu-01',
            'region' => 'eu-central',
            'host_provider' => 'example-cloud',
            'host_instance_type' => 'shared-8',
            'host_cpu_allocation' => 'shared',
            'host_purpose' => 'budget',
            'host_vcpu' => 8,
            'host_memory_mib' => 16384,
            'cloudflare_plan' => 'paid',
            'bladepdf_plan' => 'scale',
            'bladepdf_concurrency' => 8,
        ];
    }
}
