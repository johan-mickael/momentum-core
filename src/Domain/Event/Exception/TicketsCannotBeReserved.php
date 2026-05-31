<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

use Domain\Event\EventStatus;
use Domain\Event\ValueObject\EventId;

final class TicketsCannotBeReserved extends EventException
{
    public static function eventNotPublished(EventId $id, EventStatus $status): self
    {
        return new self(sprintf(
            'Cannot reserve tickets for event "%s": event status is "%s".',
            $id,
            $status->value,
        ));
    }
}
