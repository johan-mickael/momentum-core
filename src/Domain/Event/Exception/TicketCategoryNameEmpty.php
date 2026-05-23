<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

use DomainException;

final class TicketCategoryNameEmpty extends DomainException
{
    public static function create(): self
    {
        return new self('Ticket category name must not be empty.');
    }
}
