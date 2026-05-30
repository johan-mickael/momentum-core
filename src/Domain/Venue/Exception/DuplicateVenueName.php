<?php

declare(strict_types=1);

namespace App\Domain\Venue\Exception;

class DuplicateVenueName extends \DomainException
{
    public static function for(string $venueName): self
    {
        return new self(sprintf('Venue name "%s" already exists', $venueName));
    }
}
