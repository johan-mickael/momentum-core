<?php

declare(strict_types=1);

namespace Application\Order\EventListener;

use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Order\Event\OrderPaid;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\ValueObject\TicketId;

final readonly class ConfirmTicketsOnOrderPaid
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private DomainEventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(OrderPaid $event): void
    {
        foreach ($event->ticketIds as $ticketId) {
            $ticket = $this->tickets->findById(TicketId::fromString($ticketId));
            $ticket->confirm();
            $this->tickets->save($ticket);
            $this->dispatcher->dispatch(...$ticket->pullRecordedEvents());
        }
    }
}
