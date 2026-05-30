<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class CreateEventForbidden extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Create event is not allowed.');
    }
}
