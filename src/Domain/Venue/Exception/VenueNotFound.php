<?php

declare(strict_types=1);

namespace Domain\Venue\Exception;

use Domain\Venue\ValueObject\VenueId;

final class VenueNotFound extends \DomainException
{
    public static function forId(VenueId $id): self
    {
        return new self("Venue '{$id}' not found.");
    }
}
