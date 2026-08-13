<?php

namespace App\Benchmark;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;

final class HttpTransferRecorder
{
    /** @var array{request_bytes: int, response_bytes: int, http_status: int|null, response_headers: array<string, string>} */
    private array $state = [
        'request_bytes' => 0,
        'response_bytes' => 0,
        'http_status' => null,
        'response_headers' => [],
    ];

    public function reset(): void
    {
        $this->state = [
            'request_bytes' => 0,
            'response_bytes' => 0,
            'http_status' => null,
            'response_headers' => [],
        ];
    }

    public function recordRequest(Request $request): void
    {
        $this->state['request_bytes'] += strlen($request->body());
    }

    public function recordResponse(Response $response): void
    {
        $this->state['response_bytes'] += strlen($response->body());
        $this->state['http_status'] = $response->status();

        $allowed = array_map('strtolower', config('benchmark.safe_response_headers', []));

        foreach ($response->headers() as $name => $values) {
            if (in_array(strtolower($name), $allowed, true)) {
                $this->state['response_headers'][strtolower($name)] = implode(', ', $values);
            }
        }
    }

    /** @return array{request_bytes: int, response_bytes: int, http_status: int|null, response_headers: array<string, string>} */
    public function snapshot(): array
    {
        return $this->state;
    }
}
