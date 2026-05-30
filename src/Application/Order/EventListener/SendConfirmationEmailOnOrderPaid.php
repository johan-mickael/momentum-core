<?php

declare(strict_types=1);

namespace App\Application\Order\EventListener;

use App\Application\Order\Service\OrderNotificationServiceInterface;
use App\Domain\Order\Event\OrderPaid;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\ValueObject\OrderId;

final readonly class SendConfirmationEmailOnOrderPaid
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private OrderNotificationServiceInterface $notifications,
    ) {
    }

    public function __invoke(OrderPaid $domainEvent): void
    {
        $order = $this->orders->getById(OrderId::fromString($domainEvent->orderId));
        $this->notifications->sendOrderConfirmation($order);
    }
}
