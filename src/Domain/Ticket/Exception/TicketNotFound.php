<?php

declare(strict_types=1);

namespace Domain\Ticket\Exception;

use Domain\Ticket\ValueObject\TicketId;

final class TicketNotFound extends \DomainException
{
    public static function withId(TicketId $id): self
    {
        return new self(sprintf(
            'Ticket with id "%s" was not found.',
            $id->toString(),
        ));
    }
}
