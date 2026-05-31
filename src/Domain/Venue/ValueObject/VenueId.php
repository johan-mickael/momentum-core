<?php

declare(strict_types=1);

namespace Domain\Venue\ValueObject;

use Domain\Common\Id\IdInterface;
use Domain\Venue\Exception\InvalidVenueId;

final readonly class VenueId implements IdInterface
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (!uuid_is_valid($value)) {
            throw new InvalidVenueId($value);
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
