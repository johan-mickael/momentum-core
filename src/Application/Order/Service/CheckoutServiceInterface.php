<?php

declare(strict_types=1);

namespace Application\Order\Service;

use Domain\Order\Order;

interface CheckoutServiceInterface
{
    public function createSession(Order $order): CheckoutSessionData;
}
