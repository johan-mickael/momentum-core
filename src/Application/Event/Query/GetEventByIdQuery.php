<?php

declare(strict_types=1);

namespace Application\Event\Query;

final readonly class GetEventByIdQuery
{
    public function __construct(
        public string $eventId,
    ) {
    }
}
