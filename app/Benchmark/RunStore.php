<?php

namespace App\Benchmark;

use InvalidArgumentException;
use RuntimeException;

final class RunStore
{
    public function __construct(private readonly Redactor $redactor) {}

    public function path(string $runId, string $suffix = ''): string
    {
        $this->assertRunId($runId);

        return rtrim(config('benchmark.paths.runs'), '/').'/'.$runId.($suffix === '' ? '' : '/'.ltrim($suffix, '/'));
    }

    /** @param array<string, mixed> $manifest */
    public function initialize(string $runId, array $manifest, bool $resume): void
    {
        $root = $this->path($runId);

        if (is_dir($root) && ! $resume) {
            throw new RuntimeException("Run already exists: {$runId}. Use --resume to continue it.");
        }

        foreach (['raw', 'resources', 'pdfs', 'screenshots', 'diffs', 'text', 'tmp'] as $directory) {
            if (! is_dir("{$root}/{$directory}") && ! mkdir("{$root}/{$directory}", 0775, true) && ! is_dir("{$root}/{$directory}")) {
                throw new RuntimeException("Could not create run directory: {$directory}");
            }
        }

        $manifestPath = "{$root}/manifest.json";
        if (is_file($manifestPath)) {
            $existing = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
            if ($resume && $this->resumeSignature($existing) !== $this->resumeSignature($manifest)) {
                throw new RuntimeException('Resume metadata does not match the original run environment or matrix.');
            }

            return;
        }

        $this->writeJson($manifestPath, $manifest);
    }

    /** @param array<string, mixed> $row */
    public function append(string $runId, string $relativePath, array $row): void
    {
        $path = $this->path($runId, $relativePath);
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $line = json_encode($this->redactor->redact($row), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;
        if (file_put_contents($path, $line, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException("Could not append {$relativePath}");
        }
    }

    /** @param array<string, mixed> $value */
    public function write(string $runId, string $relativePath, array $value): void
    {
        $this->writeJson($this->path($runId, $relativePath), $value);
    }

    /** @return list<array<string, mixed>> */
    public function readJsonLines(string $runId, string $relativePath): array
    {
        $path = $this->path($runId, $relativePath);
        if (! is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return array_map(fn (string $line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR), $lines);
    }

    public function nextAttempt(string $runId, string $blockKey): int
    {
        $attempts = array_column(array_filter(
            $this->readJsonLines($runId, 'raw/blocks.jsonl'),
            fn (array $row) => $row['block_key'] === $blockKey,
        ), 'attempt');

        return $attempts === [] ? 1 : max($attempts) + 1;
    }

    public function hasCompleteBlock(string $runId, string $blockKey): bool
    {
        return array_any(
            $this->readJsonLines($runId, 'raw/blocks.jsonl'),
            fn (array $row) => $row['block_key'] === $blockKey && $row['status'] === 'complete',
        );
    }

    /** @return array<string, array<string, mixed>> */
    public function latestCompleteBlocks(string $runId): array
    {
        $blocks = [];
        foreach ($this->readJsonLines($runId, 'raw/blocks.jsonl') as $row) {
            if ($row['status'] !== 'complete') {
                continue;
            }

            $key = $row['block_key'];
            if (! isset($blocks[$key]) || $blocks[$key]['attempt'] < $row['attempt']) {
                $blocks[$key] = $row;
            }
        }

        return $blocks;
    }

    /** @param array<string, mixed> $value */
    private function writeJson(string $path, array $value): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $temporary = $path.'.tmp.'.getmypid();
        $json = json_encode($this->redactor->redact($value), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;
        if (file_put_contents($temporary, $json, LOCK_EX) === false || ! rename($temporary, $path)) {
            throw new RuntimeException("Could not write {$path}");
        }
    }

    private function assertRunId(string $runId): void
    {
        if (! preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $runId)) {
            throw new InvalidArgumentException('Run IDs may contain lowercase letters, numbers, and hyphens only.');
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    private function resumeSignature(array $manifest): array
    {
        $environment = $manifest['environment'] ?? [];
        if (is_array($environment)) {
            unset($environment['collected_at']);
        }

        return [
            'schema_version' => $manifest['schema_version'] ?? null,
            'profile' => $manifest['profile'] ?? null,
            'seed' => $manifest['seed'] ?? null,
            'renderers' => $manifest['renderers'] ?? null,
            'templates' => $manifest['templates'] ?? null,
            'matrix_options' => $manifest['matrix_options'] ?? null,
            'retry_policy' => $manifest['retry_policy'] ?? null,
            'environment' => $environment,
        ];
    }
}
