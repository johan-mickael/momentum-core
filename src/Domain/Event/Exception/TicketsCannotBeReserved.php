<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

use App\Domain\Event\EventStatus;
use App\Domain\Event\ValueObject\EventId;

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
