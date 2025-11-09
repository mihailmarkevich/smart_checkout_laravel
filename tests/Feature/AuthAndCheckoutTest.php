<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndCheckoutTest extends TestCase
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

    public function test_products_requires_auth(): void
    {
        $this->getJson('/api/v1/products')
            ->assertStatus(401);
    }

    public function test_can_list_products_with_jwt(): void
    {
        $token = $this->loginAndGetToken();

        $this->getJson('/api/v1/products', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200);
    }

    public function test_successful_checkout_creates_order_and_reserves_stock(): void
    {
        $token = $this->loginAndGetToken();

        $product = Product::first();
        $this->assertNotNull($product);
        $stockBefore = $product->stockItem->available_qty;

        $payload = [
            'customer' => [
                'email' => 'test-customer@example.com',
                'name'  => 'Test Customer',
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty'        => 2,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/checkout', $payload, [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200);
        $orderId = $response->json('data.id');

        $this->assertNotNull($orderId);

        $order = Order::find($orderId);
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);

        $product->refresh();
        $this->assertEquals($stockBefore - 2, $product->stockItem->available_qty);
    }
}
