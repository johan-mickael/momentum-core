<?php

declare(strict_types=1);

namespace App\Application\Order\Service;

use App\Domain\Order\Order;

interface CheckoutServiceInterface
{
    public function createSession(Order $order): string;
}
