<?php

declare(strict_types=1);

namespace Application\Ticket\Query;

final class GetTicketQuery
{
    public function __construct(
        public readonly string $ticketId,
    ) {
    }
}
