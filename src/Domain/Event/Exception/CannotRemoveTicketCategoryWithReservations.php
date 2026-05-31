<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

use Domain\Event\ValueObject\TicketCategoryId;

final class CannotRemoveTicketCategoryWithReservations extends EventException
{
    public static function forCategory(TicketCategoryId $id): self
    {
        return new self(
            sprintf('Cannot remove ticket category "%s" because it has reserved tickets.', $id->toString()),
        );
    }
}
