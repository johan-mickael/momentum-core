<?php

declare(strict_types=1);

namespace App\Application\Order\EventListener;

use App\Application\Common\Event\DomainEventDispatcherInterface;
use App\Domain\Order\Event\OrderExpired;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\ValueObject\TicketId;

final readonly class CancelTicketsOnOrderExpired
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    public function __invoke(OrderExpired $event): void
    {
        foreach ($event->ticketIds as $ticketId) {
            $ticket = $this->tickets->findById(TicketId::fromString($ticketId));
            $ticket->cancel();
            $this->tickets->save($ticket);
            $this->dispatcher->dispatch(...$ticket->pullRecordedEvents());
        }
    }
}
