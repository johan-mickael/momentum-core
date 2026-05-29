<?php

declare(strict_types=1);

namespace App\Application\Common\Result;

final readonly class PaginationMeta
{
    public readonly int $totalPages;

    public function __construct(
        public readonly int $page,
        public readonly int $limit,
        public readonly int $total,
    ) {
        $this->totalPages = $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 0;
    }
}
