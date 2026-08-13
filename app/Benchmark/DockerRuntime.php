<?php

namespace App\Benchmark;

final class DockerRuntime
{
    private const SOCKET = '/var/run/docker.sock';

    public function __construct(private readonly PrometheusMetrics $prometheus) {}

    /** @return array<string, mixed> */
    public function environment(): array
    {
        if (! file_exists(self::SOCKET)) {
            return ['observable' => false, 'reason' => 'Docker socket is unavailable.'];
        }

        $version = $this->request('GET', '/version');

        return [
            'observable' => true,
            'version' => $version['Version'] ?? null,
            'api_version' => $version['ApiVersion'] ?? null,
            'images' => [
                'app' => $this->image('laravel-pdf-benchmark:local'),
                'gotenberg' => $this->image('gotenberg/gotenberg:8.34.0-chromium'),
            ],
            'services' => [
                'gotenberg' => $this->serviceConfiguration('gotenberg'),
                'chromium-persistent' => $this->serviceConfiguration('chromium-persistent'),
            ],
        ];
    }

    public function restartGotenberg(): bool
    {
        $id = $this->serviceId('gotenberg');
        if ($id === null) {
            return false;
        }

        $this->request('POST', "/containers/{$id}/restart?t=10");
        $deadline = microtime(true) + 30;
        do {
            usleep(250_000);
            $health = @file_get_contents(rtrim((string) config('laravel-pdf.gotenberg.url'), '/').'/health');
        } while ($health === false && microtime(true) < $deadline);

        return $health !== false;
    }

    /** @return array<string, mixed>|null */
    public function sampleForRenderer(string $renderer): ?array
    {
        $service = match ($renderer) {
            'gotenberg' => 'gotenberg',
            'browsershot-persistent' => 'chromium-persistent',
            default => null,
        };

        if ($service === null || ($id = $this->serviceId($service)) === null) {
            return null;
        }

        $stats = $this->request('GET', "/containers/{$id}/stats?stream=false&one-shot=true", timeout: 1);
        $cpu = (int) ($stats['cpu_stats']['cpu_usage']['total_usage'] ?? 0);

        $result = [
            'service' => $service,
            'memory_bytes' => (int) ($stats['memory_stats']['usage'] ?? 0),
            'memory_limit_bytes' => (int) ($stats['memory_stats']['limit'] ?? 0),
            'pids' => (int) ($stats['pids_stats']['current'] ?? 0),
            'cpu_seconds_total' => $cpu / 1_000_000_000,
        ];

        if ($renderer === 'gotenberg') {
            $result = [...$result, ...$this->gotenbergTelemetry()];
        }

        return $result;
    }

    /** @return array<string, mixed>|null */
    private function image(string $name): ?array
    {
        $image = $this->request('GET', '/images/'.rawurlencode($name).'/json');
        if ($image === []) {
            return null;
        }

        return [
            'id' => isset($image['Id']) ? substr((string) $image['Id'], 0, 19) : null,
            'repo_digests' => $image['RepoDigests'] ?? [],
            'size_bytes' => $image['Size'] ?? null,
        ];
    }

    private function serviceId(string $service): ?string
    {
        $filters = rawurlencode(json_encode(['label' => [
            "com.docker.compose.service={$service}",
            'com.docker.compose.project='.config('benchmark.compose_project'),
        ]], JSON_THROW_ON_ERROR));
        $containers = $this->request('GET', "/containers/json?all=1&filters={$filters}");

        if (! isset($containers[0]) || ! is_array($containers[0])) {
            return null;
        }

        $id = $containers[0]['Id'] ?? null;

        return is_string($id) ? $id : null;
    }

    /** @return array<string, mixed>|null */
    private function serviceConfiguration(string $service): ?array
    {
        $id = $this->serviceId($service);
        if ($id === null) {
            return null;
        }
        $container = $this->request('GET', "/containers/{$id}/json");
        if ($container === []) {
            return null;
        }

        return [
            'image' => $container['Config']['Image'] ?? null,
            'command' => $container['Config']['Cmd'] ?? null,
            'platform' => $container['Platform'] ?? null,
            'cpu_limit' => $container['HostConfig']['NanoCpus'] ?? null,
            'memory_limit_bytes' => $container['HostConfig']['Memory'] ?? null,
            'status' => $container['State']['Status'] ?? null,
            'health' => $container['State']['Health']['Status'] ?? null,
        ];
    }

    /** @return array{telemetry_observable: bool, active_requests: int|null, queue_size: int|null} */
    private function gotenbergTelemetry(): array
    {
        $context = stream_context_create(['http' => ['timeout' => 1, 'ignore_errors' => true]]);
        $metrics = @file_get_contents((string) config('benchmark.gotenberg_metrics_url'), false, $context);
        if (! is_string($metrics)) {
            return ['telemetry_observable' => false, 'active_requests' => null, 'queue_size' => null];
        }

        return [
            'telemetry_observable' => true,
            'active_requests' => $this->prometheus->integerGauge($metrics, 'chromium_requests_active'),
            'queue_size' => $this->prometheus->integerGauge($metrics, 'chromium_requests_queue_size'),
        ];
    }

    /** @return array<mixed> */
    private function request(string $method, string $path, int $timeout = 3): array
    {
        if (! file_exists(self::SOCKET)) {
            return [];
        }

        $command = ['curl', '--silent', '--show-error', '--max-time', (string) $timeout, '--unix-socket', self::SOCKET, '-X', $method, 'http://localhost'.$path];
        $pipes = [];
        $process = @proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            return [];
        }
        $body = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        if ($exit !== 0 || trim((string) $body) === '') {
            return [];
        }

        return json_decode((string) $body, true) ?: [];
    }
}
