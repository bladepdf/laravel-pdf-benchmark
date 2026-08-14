<?php

namespace App\Benchmark\Drivers;

use Spatie\LaravelPdf\Drivers\CloudflareDriver;

final class UncachedCloudflareDriver extends CloudflareDriver
{
    public const int CACHE_TTL_SECONDS = 0;

    protected function endpoint(): string
    {
        return parent::endpoint().'?cacheTTL='.self::CACHE_TTL_SECONDS;
    }
}
