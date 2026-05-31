<?php

declare(strict_types=1);

namespace Application\Event\Query;

final class ListTicketCategoriesQuery
{
    public function __construct(
        public readonly string $eventId,
    ) {
    }
}
