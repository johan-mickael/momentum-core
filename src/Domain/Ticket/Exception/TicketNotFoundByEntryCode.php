<?php

declare(strict_types=1);

namespace App\Domain\Ticket\Exception;

use App\Domain\Ticket\ValueObject\EntryCode;

final class TicketNotFoundByEntryCode extends \DomainException
{
    public static function withCode(EntryCode $code): self
    {
        return new self(sprintf('Ticket with entry code "%s" not found', $code->toString()));
    }
}
