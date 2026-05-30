<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

use App\Domain\Event\ValueObject\TicketCategoryId;

final class TicketCategoryNotFound extends \DomainException
{
    public static function withId(TicketCategoryId $id): self
    {
        return new self(sprintf(
            'Ticket category with id "%s" was not found in this event.',
            $id->toString(),
        ));
    }
}
