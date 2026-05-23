<?php

declare(strict_types=1);

namespace App\Application\Ticket\Command;

final class CancelTicketCommand
{
    public function __construct(
        public readonly string $ticketId,
    ) {}
}
