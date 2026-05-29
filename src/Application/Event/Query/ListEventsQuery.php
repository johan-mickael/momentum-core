<?php

declare(strict_types=1);

namespace App\Application\Event\Query;

use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;

final readonly class ListEventsQuery
{
    public function __construct(
        public readonly PaginationCriteria $pagination,
        public readonly SortCriteria       $sort,
        public readonly ?string            $statusFilter = null,
    ) {}
}
