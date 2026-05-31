<?php

declare(strict_types=1);

namespace Domain\Ticket\Exception;

final class InvalidTicketId extends \DomainException
{
    public static function empty(): self
    {
        return new self('Ticket id must not be empty.');
    }
}
