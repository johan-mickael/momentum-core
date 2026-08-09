<?php

declare(strict_types=1);

namespace Application\Cart\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Cart\CartRepositoryInterface;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\EventRepositoryInterface;
use Psr\Clock\ClockInterface;

final readonly class ExpireCartsCommandHandler
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private EventRepositoryInterface $events,
        private DomainEventDispatcherInterface $dispatcher,
        private ClockInterface $clock,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(): void
    {
        $now = $this->clock->now();
        $candidates = $this->carts->findExpired($now);

        foreach ($candidates as $candidate) {
            $this->transactionManager->transactional(function () use ($candidate, $now) {
                // Re-read under lock: an in-flight checkout may have already
                // consumed this cart since findExpired's unlocked SELECT.
                $cart = $this->carts->findByIdForUpdate($candidate->id());

                if (!$cart->isExpired($now)) {
                    return; // touched (extended) or checked out since the sweep started
                }

                $event = $this->events->getByIdForUpdate($cart->eventId());

                foreach ($cart->items() as $item) {
                    for ($i = 0; $i < $item->quantity(); ++$i) {
                        $event->releaseTicketInCategory($item->categoryId());
                    }
                }

                $cart->expire($now);

                $this->events->save($event);
                $this->carts->save($cart);
                $this->dispatcher->dispatch(...$cart->pullRecordedEvents());
            });
        }
    }
}
