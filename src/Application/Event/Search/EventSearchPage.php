<?php

declare(strict_types=1);

namespace Application\Event\Search;

use Application\Event\Result\EventSearchResult;

final readonly class EventSearchPage
{
    /**
     * @param EventSearchResult[]               $items
     * @param array<string, EventSearchFacet[]> $facets
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
        public array $facets,
    ) {
    }
}
