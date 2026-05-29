<?php

declare(strict_types=1);

namespace App\Application\Venue\Query;

use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;

final readonly class ListVenuesQuery
{
    public function __construct(
        public readonly PaginationCriteria $pagination,
        public readonly SortCriteria       $sort,
        public readonly ?string            $cityFilter    = null,
        public readonly ?string            $countryFilter = null,
    ) {}
}
