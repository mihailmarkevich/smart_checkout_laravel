<?php

namespace App\Providers;

use App\Events\OrderPaid;
use App\Events\OrderPlaced;
use App\Listeners\DispatchOrderProcessing;
use App\Listeners\DispatchOrderSyncToWms;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [
            DispatchOrderProcessing::class,
        ],
        OrderPaid::class => [
            DispatchOrderSyncToWms::class,
        ],
    ];
}
