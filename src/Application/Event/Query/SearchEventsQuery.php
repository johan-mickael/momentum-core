<?php

declare(strict_types=1);

namespace Application\Event\Query;

final readonly class SearchEventsQuery
{
    /**
     * @param string[] $genres
     * @param string[] $cities
     */
    public function __construct(
        public ?string $query,
        public array $genres,
        public array $cities,
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
        public bool $featuredOnly,
        public int $page,
        public int $limit,
    ) {
    }
}
