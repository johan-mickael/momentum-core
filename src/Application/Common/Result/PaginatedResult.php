<?php

declare(strict_types=1);

namespace Application\Common\Result;

final class PaginatedResult
{
    public readonly PaginationMeta $pagination;

    /** @param array<mixed> $data */
    public function __construct(
        public readonly array $data,
        int $total,
        int $page,
        int $limit,
    ) {
        $this->pagination = new PaginationMeta($page, $limit, $total);
    }
}
