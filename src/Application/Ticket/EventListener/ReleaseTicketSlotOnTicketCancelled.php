<?php

declare(strict_types=1);

namespace App\Application\Ticket\EventListener;

use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Ticket\Event\TicketCancelled;

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
