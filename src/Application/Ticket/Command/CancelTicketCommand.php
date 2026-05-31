<?php

declare(strict_types=1);

namespace Application\Ticket\Command;

final class CancelTicketCommand
{
    public function __construct(
        public readonly string $ticketId,
    ) {
    }
}
