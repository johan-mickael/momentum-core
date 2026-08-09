<?php

declare(strict_types=1);

namespace Application\Event\Search\EventListener;

use Application\Event\Search\EventSearchIndexInterface;
use Domain\Event\ValueObject\EventId;
use Domain\Ticket\Event\TicketConfirmed;

final readonly class IncrementSearchPopularityOnTicketConfirmed
{
    public function __construct(
        private EventSearchIndexInterface $searchIndex,
    ) {
    }

    public function __invoke(TicketConfirmed $event): void
    {
        $this->searchIndex->incrementPopularity(EventId::fromString($event->eventId), 1);
    }
}
