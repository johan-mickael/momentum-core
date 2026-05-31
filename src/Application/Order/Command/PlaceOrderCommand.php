<?php

declare(strict_types=1);

namespace Application\Order\Command;

final readonly class PlaceOrderCommand
{
    /**
     * @param array<array{categoryId: string, quantity: int}> $items
     */
    public function __construct(
        public string $eventId,
        public array $items,
    ) {
    }
}
