<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\StockItem;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_checkout_throws_when_not_enough_stock(): void
    {
        $service = $this->app->make(OrderService::class);

        $product = Product::create([
            'sku'   => 'TEST-SKU',
            'name'  => 'Test Product',
            'price' => 10.0,
        ]);

        StockItem::create([
            'product_id'    => $product->id,
            'available_qty' => 1,
        ]);

        $payload = [
            'customer' => [
                'email' => 'unit@test.com',
                'name'  => 'Unit Test',
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty'        => 5,
                ],
            ],
        ];

        $this->expectException(\RuntimeException::class);

        $service->checkout($payload);
    }
}
