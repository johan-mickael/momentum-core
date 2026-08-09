<?php

declare(strict_types=1);

namespace Application\Event\Search\EventListener;

use Application\Event\Search\EventSearchIndexer;
use Domain\Event\DomainEvent\EventPublished;
use Domain\Event\ValueObject\EventId;

final readonly class ReindexEventOnEventPublished
{
    public function __construct(
        private EventSearchIndexer $indexer,
    ) {
    }

    public function __invoke(EventPublished $event): void
    {
        $this->indexer->reindex(EventId::fromString($event->eventId));
    }
}
