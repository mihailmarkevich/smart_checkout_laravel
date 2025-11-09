<?php

namespace App\Contracts;

interface PaymentGateway
{
    /**
     * Charge given amount for an order or other context.
     *
     * @param float $amount
     * @param array<string,mixed> $context
     */
    public function charge(float $amount, array $context = []): bool;
}
