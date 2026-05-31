<?php

declare(strict_types=1);

namespace Application\Venue\Result;

final readonly class ListVenueResult
{
    public function __construct(
        public string $id,
        public string $name,
        public string $street,
        public string $city,
        public string $country,
        public ?string $zipCode,
        public int $capacity,
        public ?float $latitude,
        public ?float $longitude,
    ) {
    }
}
