<?php

namespace App\Contracts;

use App\Models\Order;

interface OrderServiceContract
{
    public function checkout(array $payload): Order;

    public function cancel(Order $order): Order;
}
