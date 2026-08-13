<?php

namespace App\Benchmark;

use Illuminate\Http\Client\RequestException;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Throwable;

final class PdfRenderer
{
    public function __construct(
        private readonly BenchmarkData $data,
        private readonly HttpTransferRecorder $transfers,
    ) {}

    /** @return array<string, mixed> */
    public function render(string $renderer, string $template, string $assetMode = 'portable'): array
    {
        $definition = config("benchmark.renderers.{$renderer}");
        $templateDefinition = config("benchmark.templates.{$template}");

        $this->transfers->reset();
        memory_reset_peak_usage();
        $started = hrtime(true);
        $memoryBefore = memory_get_usage(true);

        try {
            $builder = Pdf::view($templateDefinition['view'], $this->data->forTemplate($template, $renderer, $assetMode))
                ->driver($definition['driver'])
                ->dontCache()
                ->format('a4');

            if ($definition['readiness'] && $template === 'javascript-chart') {
                $builder->waitUntilReady('window.pdfReady === true');
            }

            if (str_starts_with($renderer, 'browsershot')) {
                $builder->withBrowsershot(function (Browsershot $browsershot) use ($renderer, $assetMode): void {
                    $browsershot->timeout((int) config('benchmark.timeout_seconds'));
                    if ($assetMode !== 'portable') {
                        $browsershot->addChromiumArguments(['allow-file-access-from-files']);
                    }
                    if ($renderer === 'browsershot-persistent') {
                        $parts = parse_url((string) config('benchmark.persistent_chromium_url'));
                        $browsershot
                            ->setRemoteInstance($parts['host'] ?? 'chromium-persistent', (int) ($parts['port'] ?? 9222))
                            ->throwOnRemoteConnectionError();
                    }
                });
            }

            $pdf = $builder->generatePdfContent();
            if (! str_contains(substr($pdf, 0, 1024), '%PDF-')) {
                throw new \UnexpectedValueException('Renderer returned a successful response without a PDF header.');
            }
            $status = 'success';
            $errorType = null;
            $errorMessage = null;
        } catch (Throwable $throwable) {
            $pdf = null;
            $status = str_contains(strtolower($throwable->getMessage()), 'timed out') ? 'timeout' : 'failure';
            $errorType = $throwable::class;
            $errorMessage = match (true) {
                $status === 'timeout' => 'The renderer exceeded its configured timeout.',
                $throwable instanceof \UnexpectedValueException => 'The renderer response did not contain a valid PDF header.',
                $throwable instanceof RequestException => 'The renderer HTTP request failed.',
                default => 'The renderer failed; inspect the sanitized error type and HTTP status.',
            };
        }

        $transfer = $this->transfers->snapshot();
        if (isset($throwable) && $throwable instanceof RequestException && $throwable->response !== null) {
            $transfer['http_status'] = $throwable->response->status();
        }
        $finished = hrtime(true);

        return [
            'status' => $status,
            'started_monotonic_ns' => $started,
            'finished_monotonic_ns' => $finished,
            'wall_ms' => round(($finished - $started) / 1_000_000, 3),
            'php_peak_delta_bytes' => max(0, memory_get_peak_usage(true) - $memoryBefore),
            'pdf_bytes' => $pdf === null ? null : strlen($pdf),
            'pdf_sha256' => $pdf === null ? null : hash('sha256', $pdf),
            'pdf_base64' => $pdf === null ? null : base64_encode($pdf),
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            ...$transfer,
        ];
    }
}
