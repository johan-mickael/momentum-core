<?php

declare(strict_types=1);

namespace App\Domain\Ticket\Exception;

use DomainException;

final class InvalidTicketId extends DomainException
{
    public static function empty(): self
    {
        return new self('Ticket id must not be empty.');
    }
}
