<?php

declare(strict_types=1);

namespace App\Application\Event\Command;

final class PublishEventCommand
{
    public function __construct(
        public readonly string $eventId,
    ) {
    }
}
