<?php

declare(strict_types=1);

namespace App\Application\Event\Query;

final class ListTicketCategoriesQuery
{
    public function __construct(
        public readonly string $eventId,
    ) {
    }
}
