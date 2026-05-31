<?php

declare(strict_types=1);

namespace Domain\Order\Event;

use Domain\Common\Event\DomainEventInterface;

final class OrderPlaced implements DomainEventInterface
{
    /** @param string[] $ticketIds */
    public function __construct(
        public readonly string $orderId,
        public readonly string $buyerId,
        public readonly string $eventId,
        public readonly array $ticketIds,
    ) {
    }
}
