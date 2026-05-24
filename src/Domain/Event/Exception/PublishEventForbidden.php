<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

use App\Domain\Event\ValueObject\EventId;

final class PublishEventForbidden extends EventException
{
    public static function forEvent(EventId $id): self
    {
        return new self(sprintf('Publishing event "%s" is not allowed.', $id));
    }
}
