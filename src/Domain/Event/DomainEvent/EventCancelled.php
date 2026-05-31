<?php

declare(strict_types=1);

namespace Domain\Event\DomainEvent;

use Domain\Common\Event\DomainEventInterface;

final class EventCancelled implements DomainEventInterface
{
    public function __construct(
        public readonly string $eventId,
    ) {
    }
}
