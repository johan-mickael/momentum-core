<?php

declare(strict_types=1);

namespace Application\Ticket\Command;

final class MarkTicketAsUsedCommand
{
    public function __construct(
        public readonly string $ticketId,
    ) {
    }
}
