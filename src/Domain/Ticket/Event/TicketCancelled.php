<?php

declare(strict_types=1);

namespace Domain\Ticket\Event;

use Domain\Common\Event\DomainEventInterface;

final class TicketCancelled implements DomainEventInterface
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $eventId,
        public readonly string $categoryId,
    ) {
    }
}
