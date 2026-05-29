<?php

declare(strict_types=1);

namespace App\Application\Ticket\Query;

use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;

final class ListTicketsByEventQuery
{
    public function __construct(
        public readonly string             $eventId,
        public readonly PaginationCriteria $pagination,
        public readonly SortCriteria       $sort,
        public readonly ?string            $statusFilter = null,
    ) {}
}
