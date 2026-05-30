<?php

declare(strict_types=1);

namespace App\Application\Event\Result;

final readonly class VenueSummary
{
    public function __construct(
        public string $id,
        public string $name,
        public string $street,
        public string $city,
        public string $zipCode,
        public string $country,
        public ?float $latitude,
        public ?float $longitude,
    ) {
    }
}
