<?php

namespace App\Benchmark;

use Symfony\Component\Process\Process;

final class FidelityProcessor
{
    public function __construct(
        private readonly PdfInspector $inspector,
        private readonly RunStore $store,
    ) {}

    /** @return array<string, mixed> */
    public function process(string $runId): array
    {
        $root = $this->store->path($runId);
        $manifest = ['schema_version' => 1, 'reference_renderer' => 'browsershot', 'documents' => []];
        $review = ['schema_version' => 1, 'run_id' => $runId, 'updated_at' => null, 'entries' => []];
        $existingReview = $this->existingReview($root.'/fidelity-review.json');
        $runManifest = json_decode((string) file_get_contents($root.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $templates = array_intersect_key(config('benchmark.templates'), array_flip($runManifest['templates']));
        $rendererSlugs = array_values(array_filter(
            $runManifest['renderers'],
            fn (string $renderer) => (bool) config("benchmark.renderers.{$renderer}.core"),
        ));
        usort($rendererSlugs, fn (string $left, string $right) => ($left === 'browsershot' ? -1 : 0) <=> ($right === 'browsershot' ? -1 : 0));

        foreach ($templates as $template => $definition) {
            $modes = $template === 'local-assets' ? ['native-path', 'documented-remediation'] : ['portable'];
            foreach ($modes as $mode) {
                $referenceBase = "{$root}/screenshots/{$template}/browsershot__{$mode}";

                foreach ($rendererSlugs as $renderer) {
                    $pdf = "{$root}/pdfs/{$template}/{$renderer}__{$mode}.pdf";
                    $documentKey = "{$renderer}__{$template}__{$mode}";
                    foreach (config("benchmark.fidelity_features.{$template}", []) as $feature) {
                        $reviewKey = "{$documentKey}__{$feature['slug']}";
                        $review['entries'][] = $existingReview[$reviewKey] ?? [
                            'key' => $reviewKey,
                            'renderer' => $renderer,
                            'template' => $template,
                            'asset_mode' => $mode,
                            'feature' => $feature['slug'],
                            'label' => $feature['label'],
                            'page' => $feature['page'],
                            'status' => null,
                            'problem' => null,
                            'note' => null,
                            'reviewed_at' => null,
                        ];
                    }

                    if (! is_file($pdf)) {
                        $manifest['documents'][$documentKey] = ['missing_pdf' => true];

                        continue;
                    }

                    $pagePrefix = "{$root}/screenshots/{$template}/{$renderer}__{$mode}/page";
                    $inspection = $this->inspector->inspect(
                        $pdf,
                        "{$root}/text/{$template}/{$renderer}__{$mode}.txt",
                        $pagePrefix,
                    );
                    $document = [
                        'pdf' => $this->relative($root, $pdf),
                        'expected_pages' => $definition['expected_pages'],
                        'page_count_mismatch' => $inspection['page_count'] !== $definition['expected_pages'],
                        'inspection' => $inspection,
                        'pages' => [],
                    ];

                    foreach ($inspection['page_pngs'] as $pageIndex => $targetPng) {
                        $referencePng = $this->pagePath($referenceBase, $pageIndex);
                        if (! is_file($referencePng)) {
                            continue;
                        }
                        $diffPath = "{$root}/diffs/{$template}/{$renderer}__{$mode}/page-".($pageIndex + 1).'-diff.png';
                        $overlayPath = "{$root}/diffs/{$template}/{$renderer}__{$mode}/page-".($pageIndex + 1).'-overlay.png';
                        $features = array_values(array_filter(
                            config("benchmark.fidelity_features.{$template}", []),
                            fn (array $feature) => $feature['page'] === $pageIndex + 1,
                        ));
                        $metrics = $this->diff($referencePng, $targetPng, $diffPath, $overlayPath, $features);
                        $document['pages'][] = [
                            'page' => $pageIndex + 1,
                            'target' => $this->relative($root, $targetPng),
                            'reference' => $this->relative($root, $referencePng),
                            'diff' => $this->relative($root, $diffPath),
                            'overlay' => $this->relative($root, $overlayPath),
                            'metrics' => $metrics,
                        ];
                    }

                    $manifest['documents'][$documentKey] = $document;
                }
            }
        }

        $this->store->write($runId, 'raw/fidelity.json', $manifest);
        $this->store->write($runId, 'fidelity-review.json', $review);

        return $manifest;
    }

    /**
     * @param  list<array<string, mixed>>  $features
     * @return array<string, mixed>
     */
    private function diff(string $reference, string $target, string $diff, string $overlay, array $features): array
    {
        $process = new Process(['node', base_path('scripts/visual-diff.mjs'), $reference, $target, $diff, $overlay, json_encode($features, JSON_THROW_ON_ERROR)], base_path());
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            return ['error' => trim($process->getErrorOutput())];
        }

        return json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<string, array<string, mixed>> */
    private function existingReview(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data) || ! isset($data['entries']) || ! is_array($data['entries'])) {
            return [];
        }

        $entries = [];
        foreach ($data['entries'] as $entry) {
            if (is_array($entry) && isset($entry['key']) && is_string($entry['key'])) {
                $entries[$entry['key']] = $entry;
            }
        }

        return $entries;
    }

    private function pagePath(string $base, int $index): string
    {
        $pages = glob($base.'/page-*.png') ?: [];
        sort($pages, SORT_NATURAL);

        return $pages[$index] ?? '';
    }

    private function relative(string $root, string $path): string
    {
        return ltrim(str_replace($root, '', $path), '/');
    }
}
