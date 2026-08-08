<?php

declare(strict_types=1);

namespace Application\Event\Search\EventListener;

use Application\Event\Search\EventSearchIndexInterface;
use Domain\Event\ValueObject\EventId;
use Domain\Ticket\Event\TicketCancelled;

final readonly class DecrementSearchPopularityOnTicketCancelled
{
    public function __construct(
        private EventSearchIndexInterface $searchIndex,
    ) {
    }

    public function __invoke(TicketCancelled $event): void
    {
        $this->searchIndex->incrementPopularity(EventId::fromString($event->eventId), -1);
    }
}
