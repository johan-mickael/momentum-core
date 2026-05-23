<?php

declare(strict_types=1);

namespace App\Application\Ticket\Query;

final class ListTicketsByEventQuery
{
    public function __construct(
        public readonly string $eventId,
    ) {}
}
