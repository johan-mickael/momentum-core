<?php

declare(strict_types=1);

namespace Application\Event\Command;

final readonly class FeatureEventCommand
{
    public function __construct(
        public string $eventId,
        public int $rank,
    ) {
    }
}
