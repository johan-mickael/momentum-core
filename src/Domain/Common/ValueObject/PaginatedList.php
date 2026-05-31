<?php

declare(strict_types=1);

namespace Domain\Common\ValueObject;

final readonly class PaginatedList
{
    /** @param array<mixed> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit,
    ) {
    }

    public function totalPages(): int
    {
        return $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 0;
    }
}
