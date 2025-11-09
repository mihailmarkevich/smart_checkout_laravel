<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\OrderService;
use App\Contracts\OrderServiceContract;
use App\Contracts\Reports\SalesReportContract;
use App\Services\Reports\SalesReport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderServiceContract::class, OrderService::class);
        $this->app->bind(SalesReportContract::class, SalesReport::class);
    }

    public function boot(): void
    {
        //
    }
}
