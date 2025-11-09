<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user for login
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        // Add custoemrs
        $customerCount = 100;
        for ($i = 1; $i <= $customerCount; $i++) {
            Customer::updateOrCreate(
                ['email' => "customer{$i}@example.com"],
                [
                    'name'             => "Customer {$i}",
                    'shipping_address' => "Street {$i}, City {$i}",
                ]
            );
        }

        // Add products
        $namedProducts = [
            ['sku' => 'TSHIRT-BASIC-BLACK', 'name' => 'Basic T-Shirt Black',    'price' => 19.99, 'stock' => 100],
            ['sku' => 'TSHIRT-BASIC-WHITE', 'name' => 'Basic T-Shirt White',    'price' => 19.99, 'stock' => 100],
            ['sku' => 'HOODIE-GRAY',        'name' => 'Hoodie Gray',            'price' => 49.90, 'stock' => 50],
            ['sku' => 'HOODIE-NAVY',        'name' => 'Hoodie Navy',            'price' => 54.90, 'stock' => 40],
            ['sku' => 'SNEAKERS-WHITE',     'name' => 'Sneakers White',         'price' => 89.00, 'stock' => 30],
            ['sku' => 'SNEAKERS-BLACK',     'name' => 'Sneakers Black',         'price' => 89.00, 'stock' => 35],
            ['sku' => 'CAP-BLACK',          'name' => 'Baseball Cap Black',     'price' => 15.90, 'stock' => 60],
            ['sku' => 'CAP-RED',            'name' => 'Baseball Cap Red',       'price' => 15.90, 'stock' => 40],
            ['sku' => 'SOCKS-WHITE-3PACK',  'name' => 'Socks White 3-Pack',     'price' => 9.90,  'stock' => 200],
            ['sku' => 'BELT-LEATHER-BROWN', 'name' => 'Leather Belt Brown',     'price' => 29.90, 'stock' => 25],
        ];

        foreach ($namedProducts as $data) {
            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name'  => $data['name'],
                    'price' => $data['price'],
                ]
            );

            StockItem::updateOrCreate(
                ['product_id' => $product->id],
                ['available_qty' => $data['stock']]
            );
        }

        // Generic products
        $genericCount = 990;
        for ($i = 1; $i <= $genericCount; $i++) {
            $sku = sprintf('GEN-%04d', $i);
            $name = "Generated Product {$i}";
            $price = rand(500, 15000) / 100;

            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name'  => $name,
                    'price' => $price,
                ]
            );

            $stockQty = rand(50, 500);
            StockItem::updateOrCreate(
                ['product_id' => $product->id],
                ['available_qty' => $stockQty]
            );
        }

        $customers = Customer::all();
        $products  = Product::all();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        // Add Orders
        $ordersToCreate = 200;

        for ($i = 0; $i < $ordersToCreate; $i++) {
            $customer = $customers->random();

            $createdAt = now()
                ->subDays(rand(0, 30))
                ->subMinutes(rand(0, 1440));

            $statusPool = ['pending', 'paid', 'paid', 'paid', 'payment_failed', 'cancelled'];
            $status = $statusPool[array_rand($statusPool)];

            $wmsStatus = 'pending';
            $wmsSyncedAt = null;

            if ($status === 'paid') {
                $wmsStatusOptions = ['pending', 'synced', 'synced', 'failed'];
                $wmsStatus = $wmsStatusOptions[array_rand($wmsStatusOptions)];
                if ($wmsStatus === 'synced') {
                    $wmsSyncedAt = (clone $createdAt)->addHours(rand(1, 24));
                }
            }

            $order = Order::create([
                'customer_id'  => $customer->id,
                'status'       => $status,
                'total_amount' => 0,
                'wms_status'   => $wmsStatus,
                'wms_synced_at'=> $wmsSyncedAt,
                'created_at'   => $createdAt,
                'updated_at'   => $createdAt,
            ]);

            $itemsCount = rand(1, 5);
            if ($itemsCount === 1) {
                $orderProducts = collect([$products->random()]);
            } else {
                $orderProducts = $products->random($itemsCount);
            }

            $total = 0;

            foreach ($orderProducts as $product) {
                $qty = rand(1, 3);
                $price = $product->price;
                $subtotal = $price * $qty;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'qty'        => $qty,
                    'price'      => $price,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $total += $subtotal;
            }

            $order->update([
                'total_amount' => $total,
            ]);
        }
    }
}
