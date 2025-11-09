<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Log;

class FakePaymentGateway implements PaymentGateway
{
    public function charge(float $amount, array $context = []): bool
    {
        Log::info('Charging payment via FakePaymentGateway', [
            'amount'  => $amount,
            'context' => $context,
        ]);

        // Emulating the payment provider: ~90% of success
        $result = random_int(0, 100) > 10;

        Log::info('Payment result (fake)', [
            'success' => $result,
            'amount'  => $amount,
        ]);

        return $result;
    }
}
