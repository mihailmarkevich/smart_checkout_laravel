<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Wms\FakeWmsClient;
use App\Contracts\WmsClient;

class WmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(WmsClient::class, FakeWmsClient::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
