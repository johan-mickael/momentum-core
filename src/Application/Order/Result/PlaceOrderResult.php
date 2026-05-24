<?php

declare(strict_types=1);

namespace App\Application\Order\Result;

final readonly class PlaceOrderResult
{
    /** @param OrderItemResult[] $items */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly int    $totalAmount,
        public readonly string $currency,
        public readonly string $expiresAt,
        public readonly array  $items,
    ) {}
}
