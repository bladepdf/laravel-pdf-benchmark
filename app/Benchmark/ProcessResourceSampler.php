<?php

namespace App\Benchmark;

final class ProcessResourceSampler
{
    private readonly int $clockTicks;

    /** @var array<int, float> */
    private array $cpuBaseline = [];

    /** @var array<string, int|float> */
    private array $peaks = [
        'aggregate_rss_bytes' => 0,
        'process_count' => 0,
        'php_rss_bytes' => 0,
        'node_rss_bytes' => 0,
        'chrome_rss_bytes' => 0,
        'other_rss_bytes' => 0,
        'cpu_seconds_total' => 0.0,
    ];

    public function __construct()
    {
        $ticks = (int) trim((string) shell_exec('getconf CLK_TCK 2>/dev/null'));
        $this->clockTicks = $ticks > 0 ? $ticks : 100;
    }

    /** @param list<int> $rootPids */
    public function establishCpuBaseline(array $rootPids): void
    {
        foreach ($this->processRows() as $row) {
            if (in_array($row['pid'], $rootPids, true)) {
                $this->cpuBaseline[$row['pid']] = $row['cpu_seconds'];
            }
        }
    }

    /** @param list<int> $rootPids */
    public function sample(array $rootPids): void
    {
        $rows = $this->processRows();
        $included = array_fill_keys(array_filter($rootPids), true);
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($rows as $row) {
                if (isset($included[$row['ppid']]) && ! isset($included[$row['pid']])) {
                    $included[$row['pid']] = true;
                    $changed = true;
                }
            }
        }

        $current = [
            'aggregate_rss_bytes' => 0,
            'process_count' => 0,
            'php_rss_bytes' => 0,
            'node_rss_bytes' => 0,
            'chrome_rss_bytes' => 0,
            'other_rss_bytes' => 0,
            'cpu_seconds_total' => 0.0,
        ];
        $rowsByPid = [];
        foreach ($rows as $row) {
            $rowsByPid[$row['pid']] = $row;
        }

        foreach ($rows as $row) {
            if (! isset($included[$row['pid']])) {
                continue;
            }
            $bytes = $row['rss_kb'] * 1024;
            $current['aggregate_rss_bytes'] += $bytes;
            $current['process_count']++;
            $current['cpu_seconds_total'] += max(0, $row['cpu_seconds'] - ($this->cpuBaseline[$row['pid']] ?? 0));
            $category = $this->category($row, $rowsByPid);
            $current[$category] += $bytes;
        }

        foreach ($current as $key => $value) {
            $this->peaks[$key] = max($this->peaks[$key], $value);
        }
    }

    /** @return array<string, int|float> */
    public function result(): array
    {
        return $this->peaks;
    }

    /** @return list<array{pid: int, ppid: int, rss_kb: int, cpu_seconds: float, command: string}> */
    private function processRows(): array
    {
        $output = shell_exec('ps -axo pid=,ppid=,rss=,time=,comm= 2>/dev/null') ?: '';
        $rows = [];
        foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
            if (! preg_match('/^\s*(\d+)\s+(\d+)\s+(\d+)\s+([^\s]+)\s+(.+)$/', $line, $match)) {
                continue;
            }
            $rows[] = [
                'pid' => (int) $match[1],
                'ppid' => (int) $match[2],
                'rss_kb' => $this->procRssKb((int) $match[1], (int) $match[3]),
                'cpu_seconds' => $this->procCpuSeconds((int) $match[1], $this->cpuSeconds($match[4])),
                'command' => strtolower($match[5]),
            ];
        }

        return $rows;
    }

    private function cpuSeconds(string $time): float
    {
        $days = 0;
        if (str_contains($time, '-')) {
            [$dayPart, $time] = explode('-', $time, 2);
            $days = (int) $dayPart;
        }
        $parts = array_map('floatval', explode(':', $time));
        $seconds = array_pop($parts) ?: 0;
        $minutes = array_pop($parts) ?: 0;
        $hours = array_pop($parts) ?: 0;

        return ($days * 86400) + ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    private function procRssKb(int $pid, int $fallback): int
    {
        $status = @file_get_contents("/proc/{$pid}/status");
        if (is_string($status) && preg_match('/^VmRSS:\s+(\d+)\s+kB$/m', $status, $match)) {
            return (int) $match[1];
        }

        return $fallback;
    }

    private function procCpuSeconds(int $pid, float $fallback): float
    {
        $stat = @file_get_contents("/proc/{$pid}/stat");
        if (! is_string($stat) || ($end = strrpos($stat, ') ')) === false) {
            return $fallback;
        }

        $fields = preg_split('/\s+/', trim(substr($stat, $end + 2))) ?: [];
        if (! isset($fields[11], $fields[12])) {
            return $fallback;
        }

        return ((int) $fields[11] + (int) $fields[12]) / $this->clockTicks;
    }

    /**
     * @param  array{pid: int, ppid: int, rss_kb: int, cpu_seconds: float, command: string}  $row
     * @param  array<int, array{pid: int, ppid: int, rss_kb: int, cpu_seconds: float, command: string}>  $rows
     */
    private function category(array $row, array $rows): string
    {
        $current = $row;
        for ($depth = 0; $depth < 20; $depth++) {
            $category = match (true) {
                str_contains($current['command'], 'chrome'), str_contains($current['command'], 'chromium') => 'chrome_rss_bytes',
                str_contains($current['command'], 'node') => 'node_rss_bytes',
                str_contains($current['command'], 'php') => 'php_rss_bytes',
                default => null,
            };
            if ($category !== null) {
                return $category;
            }
            if (! isset($rows[$current['ppid']])) {
                break;
            }
            $current = $rows[$current['ppid']];
        }

        return 'other_rss_bytes';
    }
}
