<?php

declare(strict_types=1);

namespace App\Application\Common\Result;

final class PaginatedResult
{
    public readonly PaginationMeta $pagination;

    public function __construct(
        public readonly array $data,
        int                   $total,
        int                   $page,
        int                   $limit,
    ) {
        $this->pagination = new PaginationMeta($page, $limit, $total);
    }
}
