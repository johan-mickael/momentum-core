<?php

declare(strict_types=1);

namespace App\Application\Ticket\Command;

final class ReserveTicketCommand
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $categoryId,
    ) {}
}
