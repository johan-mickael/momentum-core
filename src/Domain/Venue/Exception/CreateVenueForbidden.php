<?php

declare(strict_types=1);

namespace Domain\Venue\Exception;

final class CreateVenueForbidden extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Create venue is not allowed.');
    }
}
