<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\ProcessOrder;

class DispatchOrderProcessing
{
    public function handle(OrderPlaced $event): void
    {
        ProcessOrder::dispatch($event->order);
    }
}
