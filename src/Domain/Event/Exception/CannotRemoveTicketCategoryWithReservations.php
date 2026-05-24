<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

use App\Domain\Event\ValueObject\TicketCategoryId;

final class CannotRemoveTicketCategoryWithReservations extends EventException
{
    public static function forCategory(TicketCategoryId $id): self
    {
        return new self(
            sprintf('Cannot remove ticket category "%s" because it has reserved tickets.', $id->toString()),
        );
    }
}
