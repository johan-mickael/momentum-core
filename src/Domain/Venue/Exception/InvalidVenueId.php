<?php

declare(strict_types=1);

namespace App\Domain\Venue\Exception;

use App\Domain\User\Exception\UserException;

final class InvalidVenueId extends UserException
{
    public static function create(string $venueId): self
    {
        return new self("Invalid venue id: $venueId");
    }
}
