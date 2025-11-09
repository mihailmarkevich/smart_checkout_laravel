<?php

namespace App\Jobs;

use App\Contracts\WmsClient;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SyncOrderToWms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public Order $order)
    {
        $this->onQueue('wms');
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(WmsClient $client): void
    {
        Log::info('Starting WMS sync job', ['order_id' => $this->order->id]);

        $success = $client->syncOrder(order: $this->order);

        if (! $success) {
            throw new RuntimeException('WMS sync failed, will retry');
        }

        $this->order->update([
            'wms_status'    => 'synced',
            'wms_synced_at' => now(),
        ]);

        Log::info('Order synced to WMS', ['order_id' => $this->order->id]);
    }

    public function failed(Throwable $exception): void
    {
        $this->order->update([
            'wms_status' => 'failed',
        ]);

        Log::error('WMS sync permanently failed', [
            'order_id' => $this->order->id,
            'error'    => $exception->getMessage(),
        ]);
    }
}
