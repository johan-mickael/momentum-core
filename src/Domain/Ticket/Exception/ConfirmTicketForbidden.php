<?php

declare(strict_types=1);

namespace Domain\Ticket\Exception;

use Domain\Ticket\ValueObject\TicketId;

final class ConfirmTicketForbidden extends \DomainException
{
    public static function forTicket(TicketId $ticketId): self
    {
        return new self(
            sprintf('Confirming ticket "%s" is not allowed.', $ticketId->toString()),
        );
    }
}
