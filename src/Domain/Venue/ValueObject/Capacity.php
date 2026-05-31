<?php

declare(strict_types=1);

namespace Domain\Venue\ValueObject;

use Domain\Venue\Exception\InvalidVenueCapacity;

final readonly class Capacity
{
    public int $value;

    private function __construct(int $value)
    {
        if ($value <= 0) {
            throw new InvalidVenueCapacity('Capacity must be greater than 0.');
        }

        $this->value = $value;
    }

    public static function fromInteger(int $value): self
    {
        return new self($value);
    }

    public static function fromString(string $value): self
    {
        $intValue = intval($value);

        return new self($intValue);
    }
}
