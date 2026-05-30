<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

class InvalidEventId extends EventException
{
    public static function invalidUuid(string $eventId): self
    {
        return new self("Invalid event id: $eventId");
    }
}
