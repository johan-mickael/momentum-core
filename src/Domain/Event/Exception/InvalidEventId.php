<?php

namespace App\Domain\Event\Exception;

class InvalidEventId extends EventException
{
    public static function invalidUuid(string $eventId): self
    {
        return new self("Invalid event id: $eventId");
    }
}
