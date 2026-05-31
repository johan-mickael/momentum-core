<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

use Domain\Event\ValueObject\EventId;

final class ViewEventForbidden extends EventException
{
    public static function forEvent(EventId $id): self
    {
        return new self(sprintf('You are not allowed to view event "%s".', $id));
    }
}
