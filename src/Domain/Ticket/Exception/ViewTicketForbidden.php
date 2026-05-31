<?php

declare(strict_types=1);

namespace Domain\Ticket\Exception;

use Domain\Ticket\ValueObject\TicketId;

final class ViewTicketForbidden extends \DomainException
{
    public static function forTicket(TicketId $ticketId): self
    {
        return new self(
            sprintf('Viewing ticket "%s" is not allowed.', $ticketId->toString()),
        );
    }
}
