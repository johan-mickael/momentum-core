<?php

declare(strict_types=1);

namespace Domain\Cart\DomainEvent;

use Domain\Common\Event\DomainEventInterface;

final readonly class CartExpired implements DomainEventInterface
{
    /**
     * @param array<int, array{categoryId: string, quantity: int}> $heldQuantities
     */
    public function __construct(
        public string $cartId,
        public array $heldQuantities,
    ) {
    }
}
