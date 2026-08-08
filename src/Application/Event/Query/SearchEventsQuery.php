<?php

declare(strict_types=1);

namespace Application\Event\Query;

final readonly class SearchEventsQuery
{
    /** @param string[] $genres */
    public function __construct(
        public ?string $query,
        public array $genres,
        public ?string $city,
        public ?string $country,
        public ?int $priceMin,
        public ?int $priceMax,
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?float $lat,
        public ?float $lon,
        public ?float $radiusKm,
        public string $sort,
        public string $direction,
        public int $page,
        public int $limit,
    ) {
    }
}
