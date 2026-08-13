<?php

namespace App\Benchmark;

final class SchemaValidator
{
    private const SLUG_PATTERN = '/^[a-z0-9][a-z0-9-]*$/';

    /**
     * @param  array<string, mixed>  $observation
     * @return list<string>
     */
    public function observation(array $observation): array
    {
        $errors = [];
        foreach (['schema_version', 'run_id', 'renderer', 'variant', 'template', 'asset_mode', 'scenario', 'phase', 'iteration', 'attempt', 'worker', 'status', 'started_monotonic_ns', 'finished_monotonic_ns', 'wall_ms', 'request_bytes', 'response_bytes'] as $key) {
            if (! array_key_exists($key, $observation)) {
                $errors[] = "Missing {$key}";
            }
        }
        if (($observation['schema_version'] ?? null) !== 1) {
            $errors[] = 'schema_version must be 1';
        }
        if (isset($observation['run_id']) && ! preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', (string) $observation['run_id'])) {
            $errors[] = 'Invalid run_id';
        }
        if (isset($observation['status']) && ! in_array($observation['status'], ['success', 'failure', 'timeout'], true)) {
            $errors[] = 'Invalid status';
        }
        foreach (['renderer', 'variant', 'template', 'asset_mode', 'scenario', 'phase'] as $key) {
            if (isset($observation[$key]) && ! preg_match(self::SLUG_PATTERN, (string) $observation[$key])) {
                $errors[] = "Invalid {$key}";
            }
        }
        foreach (['iteration', 'attempt', 'worker'] as $key) {
            if (isset($observation[$key]) && (! is_int($observation[$key]) || $observation[$key] < 1)) {
                $errors[] = "Invalid {$key}";
            }
        }
        foreach (['request_bytes', 'response_bytes'] as $key) {
            if (isset($observation[$key]) && (! is_int($observation[$key]) || $observation[$key] < 0)) {
                $errors[] = "Invalid {$key}";
            }
        }
        if (isset($observation['wall_ms']) && (! is_numeric($observation['wall_ms']) || $observation['wall_ms'] < 0)) {
            $errors[] = 'Invalid wall_ms';
        }
        foreach (['started_monotonic_ns', 'finished_monotonic_ns'] as $key) {
            if (isset($observation[$key]) && (! is_int($observation[$key]) || $observation[$key] < 0)) {
                $errors[] = "Invalid {$key}";
            }
        }
        if (isset($observation['started_monotonic_ns'], $observation['finished_monotonic_ns'])
            && $observation['finished_monotonic_ns'] < $observation['started_monotonic_ns']) {
            $errors[] = 'finished_monotonic_ns must not precede started_monotonic_ns';
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    public function manifest(array $manifest): array
    {
        $errors = [];
        foreach (['schema_version', 'run_id', 'profile', 'seed', 'created_at', 'renderers', 'templates', 'environment'] as $key) {
            if (! array_key_exists($key, $manifest)) {
                $errors[] = "Missing {$key}";
            }
        }
        if (($manifest['schema_version'] ?? null) !== 1) {
            $errors[] = 'schema_version must be 1';
        }
        if (isset($manifest['run_id']) && ! preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', (string) $manifest['run_id'])) {
            $errors[] = 'Invalid run_id';
        }
        if (isset($manifest['profile']) && ! in_array($manifest['profile'], ['smoke', 'full', 'capacity', 'fidelity'], true)) {
            $errors[] = 'Invalid profile';
        }
        if (($manifest['profile'] ?? null) === 'capacity') {
            $options = $manifest['matrix_options'] ?? null;
            if (! is_array($options) || ! isset($options['concurrency_levels'], $options['iterations'])
                || ! is_array($options['concurrency_levels']) || $options['concurrency_levels'] === []
                || ! is_int($options['iterations']) || $options['iterations'] < 1) {
                $errors[] = 'Invalid capacity matrix_options';
            }
        }
        foreach (['renderers', 'templates'] as $key) {
            if (! isset($manifest[$key]) || ! is_array($manifest[$key]) || $manifest[$key] === []) {
                $errors[] = "Invalid {$key}";

                continue;
            }
            foreach ($manifest[$key] as $slug) {
                if (! is_string($slug) || ! preg_match(self::SLUG_PATTERN, $slug)) {
                    $errors[] = "Invalid {$key} slug";
                }
            }
        }

        return $errors;
    }
}
