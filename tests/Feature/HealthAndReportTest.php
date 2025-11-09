<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthAndReportTest extends TestCase
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

    public function test_health_endpoint(): void
    {
        $this->getJson('/api/v1/health')
            ->assertStatus(200)
            ->assertJsonStructure(['status', 'checks' => ['database', 'redis']]);
    }

    public function test_sales_report_csv_returns_ok(): void
    {
        $token = $this->loginAndGetToken();

        $response = $this->get('/api/v1/reports/sales.csv', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
    }
}
