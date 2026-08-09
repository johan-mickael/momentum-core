<?php

declare(strict_types=1);

namespace Application\Cart\EventListener;

use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Cart\CartRepositoryInterface;
use Domain\Event\DomainEvent\EventCancelled;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\EventId;
use Psr\Clock\ClockInterface;

/**
 * When an organizer cancels an event, every buyer currently shopping for it
 * must lose their held slots immediately — otherwise a cart opened before
 * cancellation could still check out into an order for a dead event.
 */
final readonly class ExpireCartsOnEventCancelled
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private EventRepositoryInterface $events,
        private DomainEventDispatcherInterface $dispatcher,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(EventCancelled $domainEvent): void
    {
        $eventId = EventId::fromString($domainEvent->eventId);
        $now = $this->clock->now();

        foreach ($this->carts->findActiveByEvent($eventId) as $cart) {
            $event = $this->events->getByIdForUpdate($eventId);

            foreach ($cart->items() as $item) {
                for ($i = 0; $i < $item->quantity(); ++$i) {
                    $event->releaseTicketInCategory($item->categoryId());
                }
            }

            $cart->expire($now);

            $this->events->save($event);
            $this->carts->save($cart);
            $this->dispatcher->dispatch(...$cart->pullRecordedEvents());
        }
    }
}
