<?php

declare(strict_types=1);

namespace Application\Ticket\EventListener;

use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;
use Domain\Ticket\Event\TicketCancelled;

final readonly class ReleaseTicketSlotOnTicketCancelled
{
    public function __construct(
        private EventRepositoryInterface $events,
    ) {
    }

    public function __invoke(TicketCancelled $domainEvent): void
    {
        $event = $this->events->getById(EventId::fromString($domainEvent->eventId));
        $event->releaseTicketInCategory(TicketCategoryId::fromString($domainEvent->categoryId));
        $this->events->save($event);
    }
}
