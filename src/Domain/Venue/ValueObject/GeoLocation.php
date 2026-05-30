<?php

declare(strict_types=1);

namespace App\Domain\Venue\ValueObject;

use App\Domain\Venue\Exception\InvalidGeoLocation;

final readonly class GeoLocation
{
    private function __construct(
        private float $latitude,
        private float $longitude,
    ) {
        $this->assertValid();
    }

    public static function create(float $latitude, float $longitude): self
    {
        return new self($latitude, $longitude);
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }

    public function equals(self $other): bool
    {
        return
            $this->latitude === $other->latitude
            && $this->longitude === $other->longitude;
    }

    public function __toString(): string
    {
        return sprintf('%F,%F', $this->latitude, $this->longitude);
    }

    private function assertValid(): void
    {
        if ($this->latitude < -90 || $this->latitude > 90) {
            throw new InvalidGeoLocation('Latitude must be between -90 and 90.');
        }

        if ($this->longitude < -180 || $this->longitude > 180) {
            throw new InvalidGeoLocation('Longitude must be between -180 and 180.');
        }
    }
}
