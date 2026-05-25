<?php

declare(strict_types=1);

namespace App\Domain\Ticket\Exception;

final class ReserveTicketForbidden extends \DomainException
{
    public static function create(): self
    {
        return new self('You are not allowed to reserve a ticket.');
    }
}
