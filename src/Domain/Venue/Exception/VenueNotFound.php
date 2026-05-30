<?php

declare(strict_types=1);

namespace App\Domain\Venue\Exception;

use App\Domain\Venue\ValueObject\VenueId;

final class VenueNotFound extends \DomainException
{
    public static function forId(VenueId $id): self
    {
        return new self("Venue '{$id}' not found.");
    }
}
