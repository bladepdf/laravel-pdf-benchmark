<?php

namespace App\Benchmark;

final class Redactor
{
    private const SENSITIVE_KEYS = [
        'api_key', 'api_token', 'authorization', 'cookie', 'hostname', 'ip', 'password', 'secret', 'token',
    ];

    public function redact(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && $this->isSensitive($key)) {
            return '[REDACTED]';
        }

        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $childKey => $childValue) {
                $redacted[$childKey] = $this->redact($childValue, (string) $childKey);
            }

            return $redacted;
        }

        if (! is_string($value)) {
            return $value;
        }

        $secrets = [];
        foreach ([
            config('bladepdf.api_key'),
            config('laravel-pdf.cloudflare.api_token'),
            config('laravel-pdf.cloudflare.account_id'),
            config('laravel-pdf.gotenberg.password'),
        ] as $secret) {
            if (is_string($secret) && strlen($secret) >= 4) {
                $secrets[] = $secret;
            }
        }

        return str_replace($secrets, '[REDACTED]', $value);
    }

    private function isSensitive(string $key): bool
    {
        $key = strtolower($key);

        return array_any(self::SENSITIVE_KEYS, fn (string $needle) => str_contains($key, $needle));
    }
}
