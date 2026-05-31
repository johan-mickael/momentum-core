<?php

declare(strict_types=1);

namespace Domain\Ticket\Exception;

use Domain\Ticket\ValueObject\TicketId;

final class CancelTicketForbidden extends \DomainException
{
    public static function forTicket(TicketId $ticketId): self
    {
        return new self(
            sprintf('Cancelling ticket "%s" is not allowed.', $ticketId->toString()),
        );
    }
}
