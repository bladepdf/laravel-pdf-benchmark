<?php

namespace App\Benchmark;

use InvalidArgumentException;

final class BenchmarkMatrix
{
    /**
     * @param  list<string>  $renderers
     * @param  list<string>  $templates
     * @param  array{concurrency_levels?: list<int>, iterations?: int}  $options
     * @return list<array<string, mixed>>
     */
    public function build(string $profile, array $renderers, array $templates, int $seed, array $options = []): array
    {
        $profiles = config('benchmark.profiles');
        $rendererConfig = config('benchmark.renderers');
        $templateConfig = config('benchmark.templates');

        if (! isset($profiles[$profile])) {
            throw new InvalidArgumentException("Unknown profile: {$profile}");
        }

        foreach ($renderers as $renderer) {
            if (! isset($rendererConfig[$renderer])) {
                throw new InvalidArgumentException("Unknown renderer: {$renderer}");
            }
        }

        foreach ($templates as $template) {
            if (! isset($templateConfig[$template])) {
                throw new InvalidArgumentException("Unknown template: {$template}");
            }
        }

        $scenarios = $profiles[$profile];
        if ($profile === 'capacity') {
            $levels = $options['concurrency_levels'] ?? config('benchmark.capacity.concurrency_levels');
            $iterations = $options['iterations'] ?? config('benchmark.capacity.iterations');
            $this->validateCapacityOptions($levels, $iterations);
            foreach ($levels as $concurrency) {
                $scenarios[] = [
                    'slug' => 'concurrency-'.$concurrency,
                    'phase' => 'measured',
                    'iterations' => $iterations,
                    'concurrency' => $concurrency,
                    'measured' => true,
                ];
            }
        } elseif ($options !== []) {
            throw new InvalidArgumentException('Matrix options are supported by the capacity profile only.');
        }

        $blocks = [];
        foreach ($renderers as $renderer) {
            if ($profile === 'fidelity' && ! $rendererConfig[$renderer]['core']) {
                continue;
            }
            foreach ($templates as $template) {
                if ($profile !== 'fidelity' && ! $templateConfig[$template]['performance']) {
                    continue;
                }

                $assetModes = $profile === 'fidelity' && $template === 'local-assets'
                    ? ['native-path', 'documented-remediation']
                    : ['portable'];

                foreach ($scenarios as $scenario) {
                    foreach ($assetModes as $assetMode) {
                        $blocks[] = [
                            'renderer' => $renderer,
                            'variant' => $renderer === 'browsershot-persistent' ? 'persistent' : 'default',
                            'template' => $template,
                            'asset_mode' => $assetMode,
                            ...$scenario,
                        ];
                    }
                }
            }
        }

        usort($blocks, fn (array $a, array $b) => strcmp(
            hash('sha256', $seed.'|'.self::key($a)),
            hash('sha256', $seed.'|'.self::key($b)),
        ));

        return $blocks;
    }

    /** @param array<string, mixed> $block */
    public static function key(array $block): string
    {
        return implode('__', [$block['renderer'], $block['variant'], $block['template'], $block['asset_mode'], $block['slug']]);
    }

    /** @return list<int> */
    public static function workerIterations(int $iterations, int $concurrency, int $worker): array
    {
        $assigned = [];
        for ($iteration = $worker; $iteration <= $iterations; $iteration += $concurrency) {
            $assigned[] = $iteration;
        }

        return $assigned;
    }

    /** @param list<int> $levels */
    private function validateCapacityOptions(array $levels, int $iterations): void
    {
        if ($levels === [] || count($levels) !== count(array_unique($levels))) {
            throw new InvalidArgumentException('Capacity concurrency levels must be non-empty and unique.');
        }

        $maximumConcurrency = (int) config('benchmark.capacity.maximum_concurrency');
        foreach ($levels as $level) {
            if ($level < 1 || $level > $maximumConcurrency) {
                throw new InvalidArgumentException("Capacity concurrency levels must be integers from 1 to {$maximumConcurrency}.");
            }
        }

        $maximumIterations = (int) config('benchmark.capacity.maximum_iterations');
        if ($iterations < 1 || $iterations > $maximumIterations) {
            throw new InvalidArgumentException("Capacity iterations must be from 1 to {$maximumIterations}.");
        }
    }
}
