<?php

namespace App\Benchmark;

use Symfony\Component\Process\Process;

final class PdfInspector
{
    /** @return array<string, mixed> */
    public function inspect(string $pdf, string $textTarget, string $pagePrefix): array
    {
        if (! is_dir(dirname($textTarget))) {
            mkdir(dirname($textTarget), 0775, true);
        }
        if (! is_dir(dirname($pagePrefix))) {
            mkdir(dirname($pagePrefix), 0775, true);
        }

        $qpdf = $this->run(['qpdf', '--check', $pdf]);
        $info = $this->run(['pdfinfo', $pdf]);
        $fonts = $this->run(['pdffonts', $pdf]);
        $text = $this->run(['pdftotext', '-layout', $pdf, $textTarget]);
        $render = $this->run(['pdftoppm', '-png', '-r', '144', $pdf, $pagePrefix], timeout: 120);

        preg_match('/^Pages:\s+(\d+)/mi', $info['stdout'], $pageMatch);
        $pages = array_values(array_filter(glob($pagePrefix.'-*.png') ?: [], 'is_file'));
        sort($pages, SORT_NATURAL);

        return [
            'valid_pdf' => $qpdf['exit_code'] === 0,
            'qpdf' => $qpdf,
            'pdfinfo' => $info,
            'pdffonts' => $fonts,
            'pdftotext' => $text,
            'pdftoppm' => $render,
            'page_count' => isset($pageMatch[1]) ? (int) $pageMatch[1] : null,
            'page_pngs' => $pages,
            'extracted_text_bytes' => is_file($textTarget) ? filesize($textTarget) : null,
        ];
    }

    /**
     * @param  list<string>  $command
     * @return array{exit_code: int, stdout: string, stderr: string}
     */
    private function run(array $command, int $timeout = 30): array
    {
        try {
            $process = new Process($command, base_path());
            $process->setTimeout($timeout);
            $process->run();

            return [
                'exit_code' => $process->getExitCode() ?? 1,
                'stdout' => mb_substr(trim($process->getOutput()), 0, 10000),
                'stderr' => mb_substr(trim($process->getErrorOutput()), 0, 10000),
            ];
        } catch (\Throwable $throwable) {
            return ['exit_code' => 127, 'stdout' => '', 'stderr' => $throwable->getMessage()];
        }
    }
}
