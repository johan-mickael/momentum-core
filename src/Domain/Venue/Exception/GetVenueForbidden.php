<?php

declare(strict_types=1);

namespace Domain\Venue\Exception;

use Domain\Venue\ValueObject\VenueId;

final class GetVenueForbidden extends \DomainException
{
    public static function forId(VenueId $venueId): self
    {
        return new self(
            sprintf('Get venue: %s is not allowed', $venueId)
        );
    }
}
