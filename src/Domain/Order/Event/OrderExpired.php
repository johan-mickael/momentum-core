<?php

declare(strict_types=1);

namespace App\Domain\Order\Event;

use App\Domain\Common\Event\DomainEventInterface;

final class OrderExpired implements DomainEventInterface
{
    /** @param string[] $ticketIds */
    public function __construct(
        public readonly string $orderId,
        public readonly array $ticketIds,
    ) {}
}
