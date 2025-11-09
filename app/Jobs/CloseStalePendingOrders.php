<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CloseStalePendingOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public int $hours = 48
    ) {
    }

    public int $tries = 3;

    public function handle(): void
    {
        $threshold = now()->subHours($this->hours);

        Log::info("CloseStalePendingOrders: start, threshold={$threshold}");

        Order::query()
            ->where('status', 'pending')
            ->where('created_at', '<', $threshold)
            ->chunkById(100, function ($orders) {
                foreach ($orders as $order) {
                    $oldStatus = $order->status;

                    $order->status = 'cancelled';
                    $order->save();

                    Log::info('Order auto-cancelled', [
                        'order_id'   => $order->id,
                        'old_status' => $oldStatus,
                        'new_status' => $order->status,
                    ]);
                }
            });

        Log::info('CloseStalePendingOrders: done');
    }
}
