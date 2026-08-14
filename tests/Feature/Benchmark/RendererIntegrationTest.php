<?php

namespace Tests\Feature\Benchmark;

use App\Benchmark\BenchmarkData;
use App\Benchmark\Drivers\UncachedCloudflareDriver;
use App\Benchmark\PdfRenderer;
use BladePDF\SpatieLaravelPdf\BladePdfDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelPdf\Drivers\BrowsershotDriver;
use Spatie\LaravelPdf\Drivers\DomPdfDriver;
use Spatie\LaravelPdf\Drivers\GotenbergDriver;
use Tests\TestCase;

final class RendererIntegrationTest extends TestCase
{
    public function test_all_renderer_bindings_resolve(): void
    {
        config(['laravel-pdf.cloudflare.api_token' => 'test', 'laravel-pdf.cloudflare.account_id' => 'test']);

        $this->assertInstanceOf(DomPdfDriver::class, app('laravel-pdf.driver.dompdf'));
        $this->assertInstanceOf(BrowsershotDriver::class, app('laravel-pdf.driver.browsershot'));
        $this->assertInstanceOf(BrowsershotDriver::class, app('laravel-pdf.driver.browsershot-persistent'));
        $this->assertInstanceOf(GotenbergDriver::class, app('laravel-pdf.driver.gotenberg'));
        $this->assertInstanceOf(BladePdfDriver::class, app('laravel-pdf.driver.bladepdf'));
        $this->assertInstanceOf(UncachedCloudflareDriver::class, app('laravel-pdf.driver.cloudflare'));
    }

    public function test_dompdf_generates_a_real_pdf_through_the_shared_contract(): void
    {
        $result = app(PdfRenderer::class)->render('dompdf', 'simple-invoice');

        $this->assertSame('success', $result['status'], $result['error_message'] ?? '');
        $this->assertGreaterThan(1000, $result['pdf_bytes']);
        $this->assertNotNull($result['pdf_sha256']);
    }

    public function test_cloudflare_429_is_recorded_without_retry(): void
    {
        config(['laravel-pdf.cloudflare.api_token' => 'test', 'laravel-pdf.cloudflare.account_id' => 'account']);
        $this->app->forgetInstance('laravel-pdf.driver.cloudflare');
        Http::fake(['api.cloudflare.com/*' => Http::response('rate limited', 429, ['Retry-After' => '3'])]);

        $result = app(PdfRenderer::class)->render('cloudflare', 'simple-invoice');

        $this->assertSame('failure', $result['status']);
        $this->assertSame(429, $result['http_status']);
        $this->assertSame('3', $result['response_headers']['retry-after']);
        Http::assertSentCount(1);
    }

    public function test_cloudflare_quick_actions_cache_is_disabled(): void
    {
        config(['laravel-pdf.cloudflare.api_token' => 'test', 'laravel-pdf.cloudflare.account_id' => 'account']);
        $this->app->forgetInstance('laravel-pdf.driver.cloudflare');
        Http::fake(['api.cloudflare.com/*' => Http::response('%PDF-1.4 uncached-cloudflare', 200)]);

        $result = app(PdfRenderer::class)->render('cloudflare', 'simple-invoice');

        $this->assertSame('success', $result['status']);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'POST'
                && parse_url($request->url(), PHP_URL_PATH) === '/client/v4/accounts/account/browser-rendering/pdf'
                && $query === ['cacheTTL' => '0'];
        });
        Http::assertSentCount(1);
    }

    public function test_cloudflare_timeout_is_recorded_without_retry(): void
    {
        config(['laravel-pdf.cloudflare.api_token' => 'test', 'laravel-pdf.cloudflare.account_id' => 'account']);
        $this->app->forgetInstance('laravel-pdf.driver.cloudflare');
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('Request timed out');
        });

        $result = app(PdfRenderer::class)->render('cloudflare', 'simple-invoice');

        $this->assertSame('timeout', $result['status']);
        $this->assertSame(ConnectionException::class, $result['error_type']);
        $this->assertSame(1, $attempts);
    }

    public function test_a_successful_http_response_with_invalid_pdf_bytes_is_a_failure(): void
    {
        config(['laravel-pdf.cloudflare.api_token' => 'test', 'laravel-pdf.cloudflare.account_id' => 'account']);
        $this->app->forgetInstance('laravel-pdf.driver.cloudflare');
        Http::fake(['api.cloudflare.com/*' => Http::response('<html>not a pdf</html>', 200)]);

        $result = app(PdfRenderer::class)->render('cloudflare', 'simple-invoice');

        $this->assertSame('failure', $result['status']);
        $this->assertSame(\UnexpectedValueException::class, $result['error_type']);
        $this->assertNull($result['pdf_bytes']);
        Http::assertSentCount(1);
    }

    public function test_bladepdf_is_mocked_and_uses_one_http_attempt(): void
    {
        config([
            'bladepdf.api_key' => 'test-key',
            'bladepdf.base_url' => 'https://bladepdf.test',
            'bladepdf.retry_times' => 1,
        ]);
        Http::fake(['bladepdf.test/*' => Http::response('%PDF-1.7 mocked-bladepdf', 200)]);

        $result = app(PdfRenderer::class)->render('bladepdf', 'simple-invoice');

        $this->assertSame('success', $result['status'], $result['error_message'] ?? '');
        $this->assertSame(strlen('%PDF-1.7 mocked-bladepdf'), $result['pdf_bytes']);
        $this->assertGreaterThan(0, $result['request_bytes']);
        Http::assertSentCount(1);
    }

    public function test_missing_local_assets_remain_an_observable_fidelity_outcome(): void
    {
        config([
            'benchmark.assets.logo' => storage_path('missing/logo.png'),
            'benchmark.assets.font' => storage_path('missing/inter.woff2'),
        ]);

        $data = app(BenchmarkData::class)->forTemplate('local-assets', 'dompdf', 'native-path');
        $html = view('benchmarks.local-assets', $data)->render();

        $this->assertContains('logo', $data['missing_assets']);
        $this->assertContains('font', $data['missing_assets']);
        $this->assertStringContainsString('data-missing-assets="logo,font', $html);
    }

    public function test_readiness_and_asset_capabilities_are_explicit(): void
    {
        $this->assertFalse(config('benchmark.renderers.dompdf.readiness'));
        $this->assertFalse(config('benchmark.renderers.cloudflare.readiness'));
        $this->assertTrue(config('benchmark.renderers.browsershot.readiness'));
        $this->assertTrue(config('benchmark.renderers.gotenberg.readiness'));
        $this->assertTrue(config('benchmark.renderers.bladepdf.readiness'));
    }
}
