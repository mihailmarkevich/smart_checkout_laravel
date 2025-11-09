<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OrderService
{
    /**
     * @param array<string,mixed> $payload
     */
    public function checkout(array $payload): Order
    {
        Log::info('Starting checkout', ['payload' => $payload]);

        return DB::transaction(function () use ($payload) {
            $customerData = $payload['customer'];

            /** @var Customer $customer */
            $customer = Customer::firstOrCreate(
                ['email' => $customerData['email']],
                [
                    'name'             => $customerData['name'],
                    'shipping_address' => $customerData['shipping_address'] ?? null,
                ]
            );

            $order = new Order([
                'status'       => 'pending',
                'total_amount' => 0,
                'wms_status'   => 'pending',
            ]);
            $order->customer()->associate($customer);
            $order->save();

            $total = 0;

            foreach ($payload['items'] as $item) {
                /** @var Product $product */
                $product = Product::query()->findOrFail($item['product_id']);

                /** @var StockItem $stock */
                $stock = StockItem::query()
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    throw new RuntimeException('Stock record not found for product '.$product->id);
                }

                $qty = (int) $item['qty'];

                if ($stock->available_qty < $qty) {
                    Log::warning('Insufficient stock', [
                        'product_id'      => $product->id,
                        'requested_qty'   => $qty,
                        'available_qty'   => $stock->available_qty,
                    ]);

                    throw new RuntimeException('Not enough stock for product '.$product->sku);
                }

                $linePrice = $product->price;
                $subtotal = $linePrice * $qty;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'qty'        => $qty,
                    'price'      => $linePrice,
                ]);

                $stock->decrement('available_qty', $qty);

                $total += $subtotal;
            }

            $order->update([
                'total_amount' => $total,
            ]);

            Log::info('Order created', [
                'order_id' => $order->id,
                'total'    => $total,
            ]);

            event(new OrderPlaced($order));

            return $order;
        });
    }

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            if ($order->status === 'cancelled') {
                throw new \DomainException('Order already cancelled');
            }

            if ($order->status === 'paid' && $order->wms_status === 'synced') {
                throw new \DomainException('Cannot cancel order already synced to WMS');
            }

            Log::info('Cancelling order', ['order_id' => $order->id]);

            foreach ($order->items as $item) {
                $stock = StockItem::query()
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->increment('available_qty', $item->qty);
                }
            }

            $order->update([
                'status' => 'cancelled',
            ]);

            Log::info('Order cancelled', ['order_id' => $order->id]);

            return $order;
        });
    }
}
