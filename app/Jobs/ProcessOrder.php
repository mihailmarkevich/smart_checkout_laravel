<?php

namespace App\Jobs;

use App\Contracts\PaymentGateway;
use App\Events\OrderPaid;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function handle(PaymentGateway $gateway): void
    {
        $success = $gateway->charge($this->order->total_amount, [
            'order_id' => $this->order->id,
        ]);

        $this->order->update([
            'status' => $success ? 'paid' : 'payment_failed',
        ]);

        if ($success) {
            event(new OrderPaid($this->order));
        }
    }
}
