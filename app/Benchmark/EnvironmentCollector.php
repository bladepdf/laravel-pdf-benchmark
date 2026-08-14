<?php

namespace App\Benchmark;

use App\Benchmark\Drivers\UncachedCloudflareDriver;
use Composer\InstalledVersions;

final class EnvironmentCollector
{
    public function __construct(
        private readonly Redactor $redactor,
        private readonly DockerRuntime $docker,
    ) {}

    /** @return array<string, mixed> */
    public function collect(): array
    {
        $packages = [
            'laravel/framework',
            'spatie/laravel-pdf',
            'spatie/browsershot',
            'dompdf/dompdf',
            'bladepdf/spatie-laravel-pdf-driver',
            'bladepdf/laravel',
        ];

        $composer = [];
        foreach ($packages as $package) {
            $composer[$package] = InstalledVersions::isInstalled($package)
                ? InstalledVersions::getPrettyVersion($package)
                : null;
        }

        $cpuModel = null;
        if (is_file('/proc/cpuinfo')) {
            preg_match('/^model name\s*:\s*(.+)$/m', (string) file_get_contents('/proc/cpuinfo'), $match);
            $cpuModel = $match[1] ?? null;
        }

        $memoryBytes = null;
        if (is_file('/proc/meminfo')) {
            preg_match('/^MemTotal:\s+(\d+) kB$/m', (string) file_get_contents('/proc/meminfo'), $match);
            $memoryBytes = isset($match[1]) ? (int) $match[1] * 1024 : null;
        }

        return $this->redactor->redact([
            'collected_at' => now('UTC')->toIso8601String(),
            'declared' => config('benchmark.required_metadata'),
            'load_generation' => [
                'mode' => 'in-process-worker-pool',
                'host_relation' => 'co-located-with-application',
                'separate_host_in_timed_path' => false,
                'note' => 'Laravel workers generate load on the benchmark host. An external host may orchestrate the command, but is not the timed request generator.',
            ],
            'renderer_configuration' => [
                'cloudflare' => [
                    'quick_actions_cache_ttl_seconds' => UncachedCloudflareDriver::CACHE_TTL_SECONDS,
                    'provider_response_cache' => 'disabled',
                ],
            ],
            'platform' => [
                'os_release' => $this->fileKeyValues('/etc/os-release'),
                'kernel' => php_uname('r'),
                'architecture' => php_uname('m'),
                'cpu_model' => $cpuModel,
                'cpu_logical' => (int) ($this->command(['getconf', '_NPROCESSORS_ONLN']) ?: 0),
                'memory_bytes' => $memoryBytes,
            ],
            'runtime' => [
                'php' => PHP_VERSION,
                'node' => $this->command(['node', '--version']),
                'npm' => $this->command(['npm', '--version']),
                'composer' => $this->command(['composer', '--version', '--no-ansi']),
                'chrome' => $this->command(['node', '-e', "import('puppeteer').then(async p => { const b=await p.default.launch({headless:true,args:['--no-sandbox']}); console.log(await b.version()); await b.close(); })"]),
            ],
            'composer_packages' => $composer,
            'npm_packages' => $this->npmVersions(),
            'docker' => $this->docker->environment(),
            'git' => [
                'sha' => $this->command(['git', 'rev-parse', 'HEAD']),
                'branch' => $this->command(['git', 'branch', '--show-current']),
                'dirty' => $this->command(['git', 'status', '--porcelain']) !== '',
            ],
        ]);
    }

    /** @return array<string, string> */
    private function fileKeyValues(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $values[strtolower($key)] = trim($value, "\"'");
            }
        }

        return $values;
    }

    /** @return array<string, string|null> */
    private function npmVersions(): array
    {
        $lock = json_decode((string) file_get_contents(base_path('package-lock.json')), true);
        $versions = [];
        foreach (['puppeteer', 'tailwindcss', 'vite', 'pixelmatch', 'pngjs'] as $package) {
            $versions[$package] = $lock['packages']["node_modules/{$package}"]['version'] ?? null;
        }

        return $versions;
    }

    /** @param list<string> $command */
    private function command(array $command): string
    {
        $pipes = [];
        $process = @proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
        if (! is_resource($process)) {
            return '';
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        return $exit === 0 ? trim((string) $stdout) : '';
    }
}
