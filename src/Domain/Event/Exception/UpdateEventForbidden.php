<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

use Domain\Event\ValueObject\EventId;

final class UpdateEventForbidden extends EventException
{
    public static function forEvent(EventId $id): self
    {
        return new self(sprintf('Updating event "%s" is not allowed.', $id));
    }
}
