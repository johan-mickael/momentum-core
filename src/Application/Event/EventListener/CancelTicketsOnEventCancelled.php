<?php

declare(strict_types=1);

namespace App\Application\Event\EventListener;

use App\Application\Common\Event\DomainEventDispatcherInterface;
use App\Domain\Event\DomainEvent\EventCancelled;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\TicketStatus;

final readonly class CancelTicketsOnEventCancelled
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private DomainEventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(EventCancelled $domainEvent): void
    {
        $eventId = EventId::fromString($domainEvent->eventId);

        foreach ($this->tickets->findByEvent($eventId) as $ticket) {
            if (TicketStatus::CANCELLED === $ticket->status() || TicketStatus::USED === $ticket->status()) {
                continue;
            }

            $ticket->cancel();
            $this->tickets->save($ticket);
            $this->dispatcher->dispatch(...$ticket->pullRecordedEvents());
        }
    }
}
