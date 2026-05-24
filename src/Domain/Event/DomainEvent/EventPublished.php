<?php

declare(strict_types=1);

namespace App\Domain\Event\DomainEvent;

use App\Domain\Common\Event\DomainEventInterface;

final class EventPublished implements DomainEventInterface
{
    public function __construct(
        public readonly string $eventId,
    ) {}
}
