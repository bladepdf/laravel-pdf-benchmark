<?php

namespace App\Http\Controllers\Benchmark;

use App\Benchmark\RunStore;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ReviewController extends Controller
{
    public function show(string $run, RunStore $store): Response
    {
        $this->ensureEnabled();
        $root = $store->path($run);
        abort_unless(is_file($root.'/fidelity-review.json'), 404);

        $review = json_decode((string) file_get_contents($root.'/fidelity-review.json'), true, flags: JSON_THROW_ON_ERROR);
        $fidelity = json_decode((string) file_get_contents($root.'/raw/fidelity.json'), true, flags: JSON_THROW_ON_ERROR);

        return response()->view('benchmark.review.index', compact('run', 'review', 'fidelity'));
    }

    public function update(Request $request, string $run, RunStore $store): JsonResponse|RedirectResponse
    {
        $this->ensureEnabled();
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:300'],
            'status' => ['required', 'in:pass,partial,fail'],
            'problem' => ['nullable', 'required_if:status,partial,fail', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $path = $store->path($run, 'fidelity-review.json');
        abort_unless(is_file($path), 404);
        $review = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $found = false;
        $reviewedAt = now('UTC')->toIso8601String();
        foreach ($review['entries'] as &$entry) {
            if (hash_equals($entry['key'], $validated['key'])) {
                $entry['status'] = $validated['status'];
                $entry['problem'] = $validated['problem'] ?? null;
                $entry['note'] = $validated['note'] ?? null;
                $entry['reviewed_at'] = $reviewedAt;
                $found = true;
                break;
            }
        }
        unset($entry);
        abort_unless($found, 404);
        $review['updated_at'] = $reviewedAt;
        $store->write($run, 'fidelity-review.json', $review);

        if ($request->expectsJson()) {
            return response()->json([
                'saved' => true,
                'key' => $validated['key'],
                'reviewed_at' => $reviewedAt,
            ]);
        }

        return redirect()
            ->to(route('benchmark.review', ['run' => $run]).'#'.rawurlencode($validated['key']))
            ->with('saved', $validated['key']);
    }

    public function artifact(string $run, string $path, RunStore $store): BinaryFileResponse
    {
        $this->ensureEnabled();
        $root = realpath($store->path($run));
        $file = realpath($store->path($run, $path));
        abort_unless($root !== false && $file !== false && str_starts_with($file, $root.DIRECTORY_SEPARATOR), 404);

        return response()->file($file);
    }

    private function ensureEnabled(): void
    {
        abort_unless(app()->environment('local') && (bool) config('benchmark.review_enabled'), 404);
    }
}
