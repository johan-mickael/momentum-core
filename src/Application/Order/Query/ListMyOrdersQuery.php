<?php

declare(strict_types=1);

namespace Application\Order\Query;

final class ListMyOrdersQuery
{
    public function __construct(
        public readonly int $page,
        public readonly int $limit,
        public readonly string $sort,
        public readonly string $direction,
        public readonly ?string $statusFilter = null,
    ) {
    }
}
