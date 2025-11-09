<?php

namespace App\Services\Wms;

use App\Contracts\WmsClient;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class FakeWmsClient implements WmsClient
{
    public function syncOrder(Order $order): bool
    {
        Log::info('Syncing order to fake WMS', [
            'order_id' => $order->id,
            'status'   => $order->status,
        ]);

        sleep(1);

        // эмуляция WMS: ~80% успеха
        $success = random_int(0, 100) > 20;

        Log::info('Fake WMS sync result', [
            'order_id' => $order->id,
            'success'  => $success,
        ]);

        return $success;
    }
}
