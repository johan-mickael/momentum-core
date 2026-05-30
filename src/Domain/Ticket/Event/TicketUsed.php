<?php

declare(strict_types=1);

namespace App\Domain\Ticket\Event;

use App\Domain\Common\Event\DomainEventInterface;

final class TicketUsed implements DomainEventInterface
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $eventId,
    ) {
    }
}
