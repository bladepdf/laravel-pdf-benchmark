<?php

namespace App\Benchmark;

final class HostMetadataValidator
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>|null  $platform
     * @return array{errors: list<string>, warnings: list<string>}
     */
    public function validate(array $metadata, ?array $platform = null): array
    {
        $errors = [];
        $warnings = [];

        foreach ($metadata as $key => $value) {
            if ($value === null || $value === '') {
                $errors[] = "Missing required metadata: {$key}";
            }
        }

        if (isset($metadata['host_cpu_allocation'])
            && ! in_array($metadata['host_cpu_allocation'], ['shared', 'dedicated', 'bare-metal', 'unknown'], true)) {
            $errors[] = 'Host CPU allocation must be shared, dedicated, bare-metal, or unknown.';
        }
        if (isset($metadata['host_purpose'])
            && ! in_array($metadata['host_purpose'], ['budget', 'control', 'development'], true)) {
            $errors[] = 'Host purpose must be budget, control, or development.';
        }

        $vcpu = filter_var($metadata['host_vcpu'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($vcpu === false) {
            $errors[] = 'Host vCPU must be a positive integer.';
        }
        $memoryMiB = filter_var($metadata['host_memory_mib'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 512]]);
        if ($memoryMiB === false) {
            $errors[] = 'Host memory must be an integer of at least 512 MiB.';
        }
        if (filter_var($metadata['bladepdf_concurrency'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[] = 'BladePDF concurrency must be a positive integer.';
        }

        $hostLabel = (string) ($metadata['host_label'] ?? '');
        $hostname = gethostname();
        if (filter_var($hostLabel, FILTER_VALIDATE_IP) !== false || ($hostname !== false && hash_equals($hostname, $hostLabel))) {
            $errors[] = 'Host label must be an anonymous label, not a hostname or IP address.';
        }

        if ($platform !== null && $vcpu !== false && ($detected = (int) ($platform['cpu_logical'] ?? 0)) > 0 && $detected !== $vcpu) {
            $errors[] = "Declared host vCPU ({$vcpu}) does not match the detected logical CPU count ({$detected}).";
        }
        if ($platform !== null && $memoryMiB !== false && ($detectedBytes = (int) ($platform['memory_bytes'] ?? 0)) > 0) {
            $detectedMiB = $detectedBytes / 1_048_576;
            $difference = abs($detectedMiB - $memoryMiB) / $memoryMiB;
            if ($difference > 0.05) {
                $errors[] = sprintf(
                    'Declared host memory (%d MiB) differs from detected memory (%.0f MiB) by more than 5%%.',
                    $memoryMiB,
                    $detectedMiB,
                );
            }
        }

        if (($metadata['host_cpu_allocation'] ?? null) === 'shared') {
            $warnings[] = 'Shared-vCPU results are budget observations and may be affected by host contention; pair them with a dedicated-vCPU control run.';
        }
        if (($metadata['host_cpu_allocation'] ?? null) === 'unknown' && ($metadata['host_purpose'] ?? null) !== 'development') {
            $errors[] = 'Unknown CPU allocation is permitted only for development runs.';
        }

        return ['errors' => array_values(array_unique($errors)), 'warnings' => array_values(array_unique($warnings))];
    }
}
