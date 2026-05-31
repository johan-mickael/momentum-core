<?php

declare(strict_types=1);

namespace Domain\Ticket\Exception;

use Domain\Ticket\ValueObject\EntryCode;

final class TicketNotFoundByEntryCode extends \DomainException
{
    public static function withCode(EntryCode $code): self
    {
        return new self(sprintf('Ticket with entry code "%s" not found', $code->toString()));
    }
}
