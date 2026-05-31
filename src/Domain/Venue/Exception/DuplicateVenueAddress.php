<?php

declare(strict_types=1);

namespace Domain\Venue\Exception;

use Domain\Venue\ValueObject\Address;

class DuplicateVenueAddress extends \DomainException
{
    public static function for(Address $address): self
    {
        return new self(sprintf('Venue address "%s" already exists', $address));
    }
}
