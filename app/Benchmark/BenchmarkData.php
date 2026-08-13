<?php

namespace App\Benchmark;

final class BenchmarkData
{
    /** @return array<string, mixed> */
    public function forTemplate(string $template, string $renderer, string $assetMode = 'portable'): array
    {
        $logoPath = (string) config('benchmark.assets.logo');
        $fontPath = (string) config('benchmark.assets.font');
        $logoData = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath)) : '';
        $fontData = is_file($fontPath) ? 'data:font/woff2;base64,'.base64_encode((string) file_get_contents($fontPath)) : '';

        $native = $assetMode === 'native-path' || ($assetMode === 'documented-remediation' && $renderer === 'bladepdf');
        $vitePath = $this->builtAssetPath('resources/css/benchmarks/local-assets.css');
        $tailwindPath = $this->builtAssetPath('resources/css/app.css');

        return [
            'benchmark' => [
                'seed' => 20260809,
                'date' => '2026-08-09',
                'template' => $template,
                'renderer' => $renderer,
                'asset_mode' => $assetMode,
            ],
            'company' => ['name' => 'Northstar Research', 'email' => 'billing@northstar.invalid'],
            'customer' => ['name' => 'Acme Observatory', 'address' => '27 Meridian Way, Example City'],
            'invoice' => ['number' => 'NS-2026-0819', 'issued' => 'August 9, 2026', 'due' => 'September 8, 2026'],
            'items' => $this->items(),
            'chapters' => $this->chapters(),
            'logo_data_uri' => $logoData,
            'font_data_uri' => $fontData,
            'logo_source' => $native ? $logoPath : $logoData,
            'font_source' => $native ? $fontPath : $fontData,
            'vite_css_source' => $native ? $vitePath : null,
            'vite_css_inline' => $vitePath && is_file($vitePath)
                ? preg_replace('/url\([^)]*inter-[^)]+\.woff2\)/', "url('{$fontData}')", (string) file_get_contents($vitePath))
                : '',
            'tailwind_css' => $tailwindPath && is_file($tailwindPath) ? file_get_contents($tailwindPath) : '',
            'missing_assets' => array_values(array_filter([
                is_file($logoPath) ? null : 'logo',
                is_file($fontPath) ? null : 'font',
                $vitePath && is_file($vitePath) ? null : 'vite-css',
            ])),
        ];
    }

    /** @return list<array{description: string, quantity: int, unit: float, total: float}> */
    private function items(): array
    {
        $labels = ['Orbital analysis', 'Spectral dataset', 'Instrument calibration', 'Archive export'];
        $items = [];
        for ($i = 0; $i < 18; $i++) {
            $quantity = ($i % 4) + 1;
            $unit = 36.5 + ($i * 7.25);
            $items[] = [
                'description' => $labels[$i % count($labels)].' '.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'quantity' => $quantity,
                'unit' => $unit,
                'total' => $quantity * $unit,
            ];
        }

        return $items;
    }

    /** @return list<array{title: string, rows: list<array{label: string, value: int, change: string}>}> */
    private function chapters(): array
    {
        $chapters = [];
        for ($chapter = 1; $chapter <= 10; $chapter++) {
            $rows = [];
            for ($row = 1; $row <= 24; $row++) {
                $value = (($chapter * 37) + ($row * 19)) % 997;
                $rows[] = [
                    'label' => "Series {$chapter}.".str_pad((string) $row, 2, '0', STR_PAD_LEFT),
                    'value' => $value,
                    'change' => sprintf('%+.1f%%', (($value % 41) - 20) / 3),
                ];
            }
            $chapters[] = ['title' => "Chapter {$chapter}: Deterministic observations", 'rows' => $rows];
        }

        return $chapters;
    }

    private function builtAssetPath(string $source): ?string
    {
        $manifestPath = public_path('build/manifest.json');
        if (! is_file($manifestPath)) {
            return null;
        }
        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $file = $manifest[$source]['file'] ?? null;

        return is_string($file) ? public_path('build/'.$file) : null;
    }
}
