<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    private function loginAndGetToken(): string
    {
        /** @var User $user */
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        return $response->json('access_token');
    }

    public function test_can_cancel_pending_order_and_restore_stock(): void
    {
        $token = $this->loginAndGetToken();

        $product = Product::first();
        $stockBefore = $product->stockItem->available_qty;

        $payload = [
            'customer' => [
                'email' => 'cancel-test@example.com',
                'name'  => 'Cancel Test',
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty'        => 1,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/checkout', $payload, [
            'Authorization' => 'Bearer '.$token,
        ]);

        $orderId = $response->json('data.id');

        $cancelResponse = $this->postJson("/api/v1/orders/{$orderId}/cancel", [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $cancelResponse->assertStatus(200);

        $order = Order::find($orderId);
        $this->assertEquals('cancelled', $order->status);

        $product->refresh();
        $this->assertEquals($stockBefore, $product->stockItem->available_qty);
    }
}
