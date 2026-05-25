<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

use App\Domain\Event\ValueObject\EventId;

final class ViewEventForbidden extends EventException
{
    public static function forEvent(EventId $id): self
    {
        return new self(sprintf('You are not allowed to view event "%s".', $id));
    }
}
