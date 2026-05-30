<?php

declare(strict_types=1);

namespace App\Application\Ticket\Query;

final class ListMyTicketsQuery
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
