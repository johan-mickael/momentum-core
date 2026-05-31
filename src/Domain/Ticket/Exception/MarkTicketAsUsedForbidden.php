<?php

declare(strict_types=1);

namespace Domain\Ticket\Exception;

use Domain\Ticket\ValueObject\TicketId;

final class MarkTicketAsUsedForbidden extends \DomainException
{
    public static function forTicket(TicketId $ticketId): self
    {
        return new self(
            sprintf('Marking ticket "%s" as used is not allowed.', $ticketId->toString()),
        );
    }
}
