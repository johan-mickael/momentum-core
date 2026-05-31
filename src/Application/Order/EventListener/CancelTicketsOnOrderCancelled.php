<?php

declare(strict_types=1);

namespace Application\Order\EventListener;

use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Order\Event\OrderCancelled;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\ValueObject\TicketId;

final readonly class CancelTicketsOnOrderCancelled
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private DomainEventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(OrderCancelled $event): void
    {
        foreach ($event->ticketIds as $ticketId) {
            $ticket = $this->tickets->findById(TicketId::fromString($ticketId));
            $ticket->cancel();
            $this->tickets->save($ticket);
            $this->dispatcher->dispatch(...$ticket->pullRecordedEvents());
        }
    }
}
