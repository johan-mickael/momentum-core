<?php

declare(strict_types=1);

namespace Application\Event\Search\EventListener;

use Application\Event\Search\EventSearchIndexer;
use Domain\Event\DomainEvent\EventCancelled;
use Domain\Event\ValueObject\EventId;

final readonly class ReindexEventOnEventCancelled
{
    public function __construct(
        private EventSearchIndexer $indexer,
    ) {
    }

    public function __invoke(EventCancelled $event): void
    {
        $this->indexer->reindex(EventId::fromString($event->eventId));
    }
}
