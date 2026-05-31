<?php

declare(strict_types=1);

namespace Application\Order\EventListener;

use Application\Order\Service\OrderNotificationServiceInterface;
use Domain\Order\Event\OrderPaid;
use Domain\Order\OrderRepositoryInterface;
use Domain\Order\ValueObject\OrderId;

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
