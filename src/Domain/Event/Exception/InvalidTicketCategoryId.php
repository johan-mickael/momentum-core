<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class InvalidTicketCategoryId extends \DomainException
{
    public static function invalidUuid(string $ticketCategoryId): self
    {
        return new self("Invalid Ticket Category ID : '{$ticketCategoryId}'");
    }

    public static function empty(): self
    {
        return new self('Ticket category id must not be empty.');
    }
}
