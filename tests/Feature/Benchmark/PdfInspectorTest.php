<?php

namespace Tests\Feature\Benchmark;

use App\Benchmark\PdfInspector;
use Tests\TestCase;

final class PdfInspectorTest extends TestCase
{
    public function test_invalid_pdf_is_never_accepted_as_a_fidelity_artifact(): void
    {
        $root = storage_path('framework/testing-inspector');
        @mkdir($root, 0775, true);
        file_put_contents($root.'/invalid.pdf', 'not a pdf');

        $result = app(PdfInspector::class)->inspect($root.'/invalid.pdf', $root.'/invalid.txt', $root.'/pages/page');

        $this->assertFalse($result['valid_pdf']);
    }
}
