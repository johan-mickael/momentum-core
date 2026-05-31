<?php

declare(strict_types=1);

namespace Application\Order\Service;

use Domain\Order\Order;

interface OrderNotificationServiceInterface
{
    public function sendOrderConfirmation(Order $order): void;
}
