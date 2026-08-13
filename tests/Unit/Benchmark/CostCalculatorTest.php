<?php

namespace Tests\Unit\Benchmark;

use App\Benchmark\CostCalculator;
use PHPUnit\Framework\TestCase;

final class CostCalculatorTest extends TestCase
{
    public function test_it_calculates_multiple_deployment_scenarios_from_explicit_inputs(): void
    {
        $result = (new CostCalculator)->calculate([
            'schema_version' => 1,
            'currency' => 'TEST',
            'verified_at' => '2026-08-13',
            'price_basis' => 'tax-exclusive',
            'deployments' => [
                'example-managed' => $this->deployment([
                    'monthly_fixed' => 5,
                    'included_pdfs' => 100,
                    'per_pdf' => .01,
                    'per_browser_ms' => .001,
                    'included_average_concurrency' => 1,
                    'assumed_average_concurrency' => 2,
                    'per_additional_concurrency_month' => 3,
                ]),
                'example-existing' => $this->deployment([
                    'label' => 'Existing capacity',
                    'category' => 'existing-capacity',
                    'managed' => false,
                    'operational_responsibility' => 'High',
                ]),
            ],
        ], ['example' => ['mean_pdf_bytes' => 1000, 'mean_browser_ms' => 2]]);

        $this->assertSame(19.0, $result['scenarios']['1000']['example-managed']['direct_cost']);
        $this->assertSame(3.0, $result['scenarios']['1000']['example-managed']['breakdown']['browser_concurrency']);
        $this->assertSame(0.0, $result['scenarios']['1000']['example-existing']['direct_cost']);
        $this->assertSame('existing-capacity', $result['deployments']['example-existing']['category']);
    }

    public function test_it_rejects_incomplete_pricing_snapshots(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a non-negative number');

        (new CostCalculator)->calculate([
            'schema_version' => 1,
            'currency' => 'TEST',
            'verified_at' => '2026-08-13',
            'price_basis' => 'tax-exclusive',
            'deployments' => ['example' => $this->deployment(['monthly_fixed' => null])],
        ], []);
    }

    public function test_usage_pricing_requires_the_corresponding_measured_metric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires measured browser milliseconds');

        (new CostCalculator)->calculate([
            'schema_version' => 1,
            'currency' => 'TEST',
            'verified_at' => '2026-08-13',
            'price_basis' => 'tax-exclusive',
            'deployments' => ['example' => $this->deployment(['per_browser_ms' => .001])],
        ], []);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function deployment(array $overrides = []): array
    {
        return array_replace([
            'label' => 'Managed example',
            'renderer' => 'example',
            'category' => 'managed-service',
            'managed' => true,
            'target_concurrency' => 2,
            'operational_responsibility' => 'Low',
            'source_url' => 'https://example.test/pricing',
            'monthly_fixed' => 0,
            'server_monthly' => 0,
            'included_pdfs' => 0,
            'per_pdf' => 0,
            'included_browser_ms' => 0,
            'per_browser_ms' => 0,
            'storage_retention_months' => 0,
            'storage_per_gb_month' => 0,
            'egress_per_gb' => 0,
            'included_average_concurrency' => 0,
            'assumed_average_concurrency' => 0,
            'per_additional_concurrency_month' => 0,
        ], $overrides);
    }
}
