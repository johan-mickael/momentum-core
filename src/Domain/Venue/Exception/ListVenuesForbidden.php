<?php

declare(strict_types=1);

namespace App\Domain\Venue\Exception;

final class ListVenuesForbidden extends \DomainException
{
    public function __construct()
    {
        parent::__construct('List venues is not allowed.');
    }
}
