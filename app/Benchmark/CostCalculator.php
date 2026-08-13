<?php

namespace App\Benchmark;

use InvalidArgumentException;

final class CostCalculator
{
    private const CATEGORIES = [
        'existing-capacity',
        'enlarged-application-server',
        'dedicated-render-server',
        'managed-service',
    ];

    private const NUMERIC_INPUTS = [
        'monthly_fixed',
        'server_monthly',
        'included_pdfs',
        'per_pdf',
        'included_browser_ms',
        'per_browser_ms',
        'storage_retention_months',
        'storage_per_gb_month',
        'egress_per_gb',
        'included_average_concurrency',
        'assumed_average_concurrency',
        'per_additional_concurrency_month',
    ];

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, array<string, mixed>>  $rendererMetrics
     * @return array<string, mixed>
     */
    public function calculate(array $snapshot, array $rendererMetrics): array
    {
        foreach (['schema_version', 'currency', 'verified_at', 'price_basis', 'deployments'] as $required) {
            if (! array_key_exists($required, $snapshot)) {
                throw new InvalidArgumentException("Cost snapshot is missing {$required}.");
            }
        }
        if ($snapshot['schema_version'] !== 1) {
            throw new InvalidArgumentException('Cost snapshot schema_version must be 1.');
        }
        if (! is_string($snapshot['currency']) || trim($snapshot['currency']) === '') {
            throw new InvalidArgumentException('Cost snapshot currency must be a non-empty string.');
        }
        if (! is_string($snapshot['verified_at']) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $snapshot['verified_at']) !== 1) {
            throw new InvalidArgumentException('Cost snapshot verified_at must use YYYY-MM-DD.');
        }
        if (! in_array($snapshot['price_basis'], ['tax-exclusive', 'tax-inclusive'], true)) {
            throw new InvalidArgumentException('Cost snapshot price_basis must be tax-exclusive or tax-inclusive.');
        }
        if (! is_array($snapshot['deployments']) || $snapshot['deployments'] === []) {
            throw new InvalidArgumentException('Cost snapshot deployments must be a non-empty object.');
        }

        $deployments = [];
        foreach ($snapshot['deployments'] as $slug => $deployment) {
            if (! is_string($slug) || preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug) !== 1 || ! is_array($deployment)) {
                throw new InvalidArgumentException('Cost deployment slugs must be stable lowercase slugs.');
            }
            $deployments[$slug] = $this->validateDeployment($slug, $deployment, $rendererMetrics);
        }

        $result = [
            'schema_version' => 1,
            'currency' => $snapshot['currency'],
            'verified_at' => $snapshot['verified_at'],
            'price_basis' => $snapshot['price_basis'],
            'tax_note' => $snapshot['tax_note'] ?? null,
            'notes' => $snapshot['notes'] ?? null,
            'deployments' => [],
            'scenarios' => [],
        ];

        foreach ($deployments as $slug => $deployment) {
            $result['deployments'][$slug] = $this->deploymentMetadata($deployment);
        }
        foreach ([1000, 10000, 100000] as $volume) {
            foreach ($deployments as $slug => $deployment) {
                $renderer = $deployment['renderer'];
                $metrics = $rendererMetrics[$renderer] ?? [];
                $billablePdfs = max(0, $volume - (int) $deployment['included_pdfs']);
                $bytes = (float) ($metrics['mean_pdf_bytes'] ?? 0) * $volume;
                $browserMs = (float) ($metrics['mean_browser_ms'] ?? 0) * $volume;
                $billableBrowserMs = max(0, $browserMs - (float) $deployment['included_browser_ms']);
                $additionalConcurrency = max(
                    0,
                    (float) $deployment['assumed_average_concurrency'] - (float) $deployment['included_average_concurrency'],
                );

                $breakdown = [
                    'monthly_fixed' => (float) $deployment['monthly_fixed'],
                    'server' => (float) $deployment['server_monthly'],
                    'pdf_usage' => $billablePdfs * (float) $deployment['per_pdf'],
                    'browser_time' => $billableBrowserMs * (float) $deployment['per_browser_ms'],
                    'browser_concurrency' => $additionalConcurrency * (float) $deployment['per_additional_concurrency_month'],
                    'storage' => ($bytes / 1_000_000_000)
                        * (float) $deployment['storage_retention_months']
                        * (float) $deployment['storage_per_gb_month'],
                    'egress' => ($bytes / 1_000_000_000) * (float) $deployment['egress_per_gb'],
                ];
                $breakdown = array_map(fn (float $cost) => round($cost, 4), $breakdown);

                $result['scenarios'][(string) $volume][$slug] = [
                    ...$this->deploymentMetadata($deployment),
                    'direct_cost' => round(array_sum($breakdown), 4),
                    'breakdown' => $breakdown,
                    'inputs' => [
                        'pdfs' => $volume,
                        'billable_pdfs' => $billablePdfs,
                        'estimated_gb' => round($bytes / 1_000_000_000, 6),
                        'measured_browser_ms' => round($browserMs, 3),
                        'billable_browser_ms' => round($billableBrowserMs, 3),
                        'billable_average_concurrency' => round($additionalConcurrency, 3),
                    ],
                ];
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $deployment
     * @param  array<string, array<string, mixed>>  $rendererMetrics
     * @return array<string, mixed>
     */
    private function validateDeployment(string $slug, array $deployment, array $rendererMetrics): array
    {
        foreach (['label', 'renderer', 'category', 'managed', 'target_concurrency', 'operational_responsibility', 'source_url'] as $required) {
            if (! array_key_exists($required, $deployment)) {
                throw new InvalidArgumentException("Cost deployment {$slug} is missing {$required}.");
            }
        }
        if (! is_string($deployment['label']) || trim($deployment['label']) === '') {
            throw new InvalidArgumentException("Cost deployment {$slug}.label must be a non-empty string.");
        }
        if (! is_string($deployment['renderer']) || preg_match('/^[a-z0-9][a-z0-9-]*$/', $deployment['renderer']) !== 1) {
            throw new InvalidArgumentException("Cost deployment {$slug}.renderer must be a stable slug.");
        }
        if (! in_array($deployment['category'], self::CATEGORIES, true)) {
            throw new InvalidArgumentException("Cost deployment {$slug}.category is invalid.");
        }
        if (! is_bool($deployment['managed'])) {
            throw new InvalidArgumentException("Cost deployment {$slug}.managed must be boolean.");
        }
        if (($deployment['category'] === 'managed-service') !== $deployment['managed']) {
            throw new InvalidArgumentException("Cost deployment {$slug}.managed must match its category.");
        }
        if ($deployment['target_concurrency'] !== null
            && (! is_int($deployment['target_concurrency']) || $deployment['target_concurrency'] < 1)) {
            throw new InvalidArgumentException("Cost deployment {$slug}.target_concurrency must be null or a positive integer.");
        }
        if (! in_array($deployment['operational_responsibility'], ['Low', 'Medium', 'High'], true)) {
            throw new InvalidArgumentException("Cost deployment {$slug}.operational_responsibility must be Low, Medium, or High.");
        }
        $source = is_string($deployment['source_url']) ? parse_url($deployment['source_url']) : false;
        if (! is_array($source) || ($source['scheme'] ?? null) !== 'https' || ! isset($source['host'])
            || isset($source['user']) || isset($source['pass']) || isset($source['query']) || isset($source['fragment'])) {
            throw new InvalidArgumentException("Cost deployment {$slug}.source_url must be a credential-free canonical HTTPS URL.");
        }
        foreach (self::NUMERIC_INPUTS as $input) {
            if (! array_key_exists($input, $deployment) || ! is_numeric($deployment[$input]) || (float) $deployment[$input] < 0) {
                throw new InvalidArgumentException("Cost snapshot input {$slug}.{$input} must be a non-negative number.");
            }
        }

        $renderer = $deployment['renderer'];
        $metrics = $rendererMetrics[$renderer] ?? [];
        if ((float) $deployment['per_browser_ms'] > 0 && ! isset($metrics['mean_browser_ms'])) {
            throw new InvalidArgumentException("Cost deployment {$slug} requires measured browser milliseconds for {$renderer}.");
        }
        if (((float) $deployment['storage_per_gb_month'] > 0 || (float) $deployment['egress_per_gb'] > 0)
            && ! isset($metrics['mean_pdf_bytes'])) {
            throw new InvalidArgumentException("Cost deployment {$slug} requires measured PDF bytes for {$renderer}.");
        }

        return $deployment;
    }

    /**
     * @param  array<string, mixed>  $deployment
     * @return array<string, mixed>
     */
    private function deploymentMetadata(array $deployment): array
    {
        return [
            'label' => $deployment['label'],
            'renderer' => $deployment['renderer'],
            'category' => $deployment['category'],
            'managed' => $deployment['managed'],
            'target_concurrency' => $deployment['target_concurrency'],
            'operational_responsibility' => $deployment['operational_responsibility'],
            'source_url' => $deployment['source_url'],
            'price_notes' => $deployment['price_notes'] ?? null,
            'infrastructure' => $deployment['infrastructure'] ?? null,
        ];
    }
}
