<?php

declare(strict_types=1);

namespace Domain\Venue\Exception;

use Domain\User\Exception\UserException;

final class InvalidVenueId extends UserException
{
    public static function create(string $venueId): self
    {
        return new self("Invalid venue id: $venueId");
    }
}
