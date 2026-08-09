<?php

declare(strict_types=1);

namespace Application\Event\Result;

use Application\Common\Result\PaginationMeta;
use Application\Event\Search\EventSearchFacet;

final class SearchEventsResult
{
    public readonly PaginationMeta $pagination;

    /**
     * @param EventSearchResult[]               $data
     * @param array<string, EventSearchFacet[]> $facets
     */
    public function __construct(
        public readonly array $data,
        public readonly array $facets,
        int $total,
        int $page,
        int $limit,
    ) {
        $this->pagination = new PaginationMeta($page, $limit, $total);
    }
}
