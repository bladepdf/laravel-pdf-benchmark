<?php

namespace Tests\Unit\Benchmark;

use App\Benchmark\Redactor;
use Tests\TestCase;

final class RedactorTest extends TestCase
{
    public function test_it_redacts_sensitive_keys_recursively(): void
    {
        $result = (new Redactor)->redact([
            'safe' => 'EU benchmark',
            'api_token' => 'secret-value',
            'nested' => ['hostname' => 'private-host', 'count' => 3],
        ]);

        $this->assertSame('EU benchmark', $result['safe']);
        $this->assertSame('[REDACTED]', $result['api_token']);
        $this->assertSame('[REDACTED]', $result['nested']['hostname']);
        $this->assertSame(3, $result['nested']['count']);
    }

    public function test_it_redacts_configured_credentials_embedded_in_messages(): void
    {
        config([
            'bladepdf.api_key' => 'bladepdf-test-secret',
            'laravel-pdf.cloudflare.api_token' => 'cloudflare-test-secret',
            'laravel-pdf.cloudflare.account_id' => 'account-identifier',
        ]);

        $result = (new Redactor)->redact('bladepdf-test-secret cloudflare-test-secret account-identifier');

        $this->assertSame('[REDACTED] [REDACTED] [REDACTED]', $result);
    }

    public function test_it_does_not_redact_safe_keys_containing_sensitive_substrings(): void
    {
        $value = [
            'browsershot__javascript-chart__portable' => ['pdf' => 'pdfs/javascript-chart.pdf'],
            'ownership' => ['queue' => 'provider'],
            'description' => 'Deterministic fixture',
        ];

        $this->assertSame($value, (new Redactor)->redact($value));
    }

    public function test_it_redacts_sensitive_key_segments_across_common_naming_styles(): void
    {
        $result = (new Redactor)->redact([
            'cloudflare_api_token_sha256' => 'token-hash',
            'clientSecret' => 'client-secret',
            'request.ip_address' => '192.0.2.1',
            'authorization-header' => 'Bearer token',
        ]);

        $this->assertSame('[REDACTED]', $result['cloudflare_api_token_sha256']);
        $this->assertSame('[REDACTED]', $result['clientSecret']);
        $this->assertSame('[REDACTED]', $result['request.ip_address']);
        $this->assertSame('[REDACTED]', $result['authorization-header']);
    }
}
