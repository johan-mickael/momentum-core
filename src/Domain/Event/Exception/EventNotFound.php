<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

use App\Domain\Event\ValueObject\EventId;

final class EventNotFound extends \DomainException
{
    public static function forId(EventId $id): self
    {
        return new self("Event '{$id}' not found.");
    }
}
