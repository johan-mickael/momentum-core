<?php

declare(strict_types=1);

namespace App\Application\Venue\Command;

final readonly class CreateVenueCommand
{
    public function __construct(
        public string $name,
        public string $street,
        public string $zipCode,
        public string $city,
        public string $country,
        public int $capacity,
        public float $latitude,
        public float $longitude,
    ) {
    }
}
