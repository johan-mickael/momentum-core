<?php

declare(strict_types=1);

namespace App\Application\Event\Query;

final readonly class GetEventByIdQuery
{
    public function __construct(
        public string $eventId,
    ) {}
}
