<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Jobs\SyncOrderToWms;

class DispatchOrderSyncToWms
{
    public function handle(OrderPaid $event): void
    {
        SyncOrderToWms::dispatch($event->order);
    }
}
