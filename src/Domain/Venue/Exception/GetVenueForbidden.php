<?php

declare(strict_types=1);

namespace App\Domain\Venue\Exception;

use App\Domain\Venue\ValueObject\VenueId;

final class GetVenueForbidden extends \DomainException
{
    public static function forId(VenueId $venueId): self
    {
        return new self(
            sprintf('Get venue: %s is not allowed', $venueId)
        );
    }
}
