<?php

declare(strict_types=1);

namespace Domain\Ticket\Exception;

final class ListTicketsForbidden extends \DomainException
{
    public static function create(): self
    {
        return new self('Listing tickets is not allowed.');
    }
}
