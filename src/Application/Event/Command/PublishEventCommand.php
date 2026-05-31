<?php

declare(strict_types=1);

namespace Application\Event\Command;

final class PublishEventCommand
{
    public function __construct(
        public readonly string $eventId,
    ) {
    }
}
