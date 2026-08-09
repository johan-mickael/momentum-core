<?php

declare(strict_types=1);

namespace Application\Event\Search;

use Domain\Common\ValueObject\PaginationCriteria;

final readonly class EventSearchCriteria
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
        public PaginationCriteria $pagination,
        public bool $publishedOnly,
        public bool $featuredOnly,
    ) {
    }

    public function hasGeoFilter(): bool
    {
        return null !== $this->lat && null !== $this->lon && null !== $this->radiusKm;
    }
}
