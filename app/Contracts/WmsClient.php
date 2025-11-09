<?php

namespace App\Contracts;

use App\Models\Order;

interface WmsClient
{
    /**
     * Synchronize given order with external WMS.
     *
     * Return true on success, false on recoverable failure.
     */
    public function syncOrder(Order $order): bool;
}
