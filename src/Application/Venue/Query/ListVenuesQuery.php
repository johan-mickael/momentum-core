<?php

declare(strict_types=1);

namespace App\Application\Venue\Query;

final readonly class ListVenuesQuery
{
    public function __construct(
        public readonly int     $page,
        public readonly int     $limit,
        public readonly string  $sort,
        public readonly string  $direction,
        public readonly ?string $cityFilter    = null,
        public readonly ?string $countryFilter = null,
    ) {}
}
