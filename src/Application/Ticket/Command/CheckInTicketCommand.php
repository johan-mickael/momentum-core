<?php

declare(strict_types=1);

namespace Application\Ticket\Command;

final class CheckInTicketCommand
{
    public function __construct(
        public readonly string $entryCode,
    ) {
    }
}
