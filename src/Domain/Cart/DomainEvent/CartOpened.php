<?php

declare(strict_types=1);

namespace Domain\Cart\DomainEvent;

use Domain\Common\Event\DomainEventInterface;

final readonly class CartOpened implements DomainEventInterface
{
    public function __construct(
        public string $cartId,
        public string $buyerId,
        public string $eventId,
    ) {
    }
}
