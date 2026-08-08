<?php

declare(strict_types=1);

namespace Application\Event\Search\EventListener;

use Application\Event\Search\EventSearchIndexer;
use Domain\Event\DomainEvent\EventCreated;
use Domain\Event\ValueObject\EventId;

final readonly class ReindexEventOnEventCreated
{
    public function __construct(
        private EventSearchIndexer $indexer,
    ) {
    }

    public function __invoke(EventCreated $event): void
    {
        $this->indexer->reindex(EventId::fromString($event->eventId));
    }
}
