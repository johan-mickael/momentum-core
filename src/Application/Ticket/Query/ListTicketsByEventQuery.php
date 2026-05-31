<?php

declare(strict_types=1);

namespace Application\Ticket\Query;

final class ListTicketsByEventQuery
{
    public function __construct(
        public readonly string $eventId,
        public readonly int $page,
        public readonly int $limit,
        public readonly string $sort,
        public readonly string $direction,
        public readonly ?string $statusFilter = null,
    ) {
    }
}
