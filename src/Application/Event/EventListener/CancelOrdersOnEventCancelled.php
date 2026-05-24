<?php

declare(strict_types=1);

namespace App\Application\Event\EventListener;

use App\Application\Common\Event\DomainEventDispatcherInterface;
use App\Domain\Event\DomainEvent\EventCancelled;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Order\OrderRepositoryInterface;
use Psr\Clock\ClockInterface;

final readonly class CancelOrdersOnEventCancelled
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private DomainEventDispatcherInterface $dispatcher,
        private ClockInterface $clock,
    ) {}

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
