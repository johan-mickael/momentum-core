<?php

declare(strict_types=1);

namespace Application\Event\Search;

final readonly class EventSearchDocument
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public string $genre,
        public string $status,
        public string $startAt,
        public string $endAt,
        public string $slug,
        public ?string $imageUrl,
        public string $venueId,
        public string $venueName,
        public string $venueStreet,
        public string $venueCity,
        public string $venueZipCode,
        public string $venueCountry,
        public ?float $venueLatitude,
        public ?float $venueLongitude,
        public ?int $minPrice,
        public ?int $maxPrice,
        public ?string $currency,
    ) {
    }
}
