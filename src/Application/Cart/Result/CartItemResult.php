<?php

declare(strict_types=1);

namespace Application\Cart\Result;

final readonly class CartItemResult
{
    public function __construct(
        public string $categoryId,
        public string $categoryName,
        public int $quantity,
        public int $unitPriceAmount,
        public string $unitPriceCurrency,
        public int $subtotalAmount,
        public int $availableQuantity,
    ) {
    }
}
