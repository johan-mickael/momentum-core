<?php

declare(strict_types=1);

namespace App\Application\Order\Result;

final readonly class OrderItemResult
{
    public function __construct(
        public readonly string $categoryId,
        public readonly string $ticketId,
        public readonly int    $unitPriceAmount,
        public readonly string $unitPriceCurrency,
    ) {}
}
