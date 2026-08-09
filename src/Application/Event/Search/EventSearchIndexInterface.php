<?php

declare(strict_types=1);

namespace Application\Event\Search;

use Domain\Event\ValueObject\EventId;

interface EventSearchIndexInterface
{
    /**
     * Upserts the document's fields, leaving the ES-only `popularity`
     * counter untouched on existing documents (initialised to 0 only
     * when the document is created for the first time).
     */
    public function index(EventSearchDocument $document): void;

    public function incrementPopularity(EventId $id, int $delta): void;

    public function search(EventSearchCriteria $criteria): EventSearchPage;
}
