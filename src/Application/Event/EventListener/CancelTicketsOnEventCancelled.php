<?php

declare(strict_types=1);

namespace Application\Event\EventListener;

use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Event\DomainEvent\EventCancelled;
use Domain\Event\ValueObject\EventId;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\TicketStatus;

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

        foreach ($this->tickets->findAllByEvent($eventId) as $ticket) {
            if (TicketStatus::CANCELLED === $ticket->status() || TicketStatus::USED === $ticket->status()) {
                continue;
            }

            $ticket->cancel();
            $this->tickets->save($ticket);
            $this->dispatcher->dispatch(...$ticket->pullRecordedEvents());
        }
    }
}
