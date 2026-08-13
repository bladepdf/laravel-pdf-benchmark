<?php

namespace App\Providers;

use App\Benchmark\HttpTransferRecorder;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPdf\Drivers\BrowsershotDriver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HttpTransferRecorder::class);

        $this->app->bind('laravel-pdf.driver.browsershot-persistent', fn () => new BrowsershotDriver(
            config('laravel-pdf.browsershot', []),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(RequestSending::class, function (RequestSending $event): void {
            app(HttpTransferRecorder::class)->recordRequest($event->request);
        });

        Event::listen(ResponseReceived::class, function (ResponseReceived $event): void {
            app(HttpTransferRecorder::class)->recordResponse($event->response);
        });
    }
}
