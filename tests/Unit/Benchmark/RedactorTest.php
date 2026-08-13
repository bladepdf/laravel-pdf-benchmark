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
}
