<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

use Domain\Event\ValueObject\EventId;

final class AddTicketCategoryForbidden extends EventException
{
    public static function forEvent(EventId $eventId): self
    {
        return new self(
            sprintf('Adding a ticket category to event "%s" is not allowed.', $eventId),
        );
    }
}
