<?php

declare(strict_types=1);

namespace Application\Event\EventListener;

use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Event\DomainEvent\EventCancelled;
use Domain\Event\ValueObject\EventId;
use Domain\Order\OrderRepositoryInterface;
use Psr\Clock\ClockInterface;

final readonly class CancelOrdersOnEventCancelled
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private DomainEventDispatcherInterface $dispatcher,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(EventCancelled $domainEvent): void
    {
        $eventId = EventId::fromString($domainEvent->eventId);
        $now = $this->clock->now();

        foreach ($this->orders->findPendingByEventId($eventId) as $order) {
            $order->cancel($now);
            $this->orders->save($order);
            $this->dispatcher->dispatch(...$order->pullRecordedEvents());
        }
    }
}
