<?php

declare(strict_types=1);

namespace Application\Cart\Result;

final readonly class CartResult
{
    /** @param CartItemResult[] $items */
    public function __construct(
        public ?string $id,
        public string $status,
        public ?string $expiresAt,
        public array $items,
        public int $totalAmount,
        public string $currency,
    ) {
    }

    public static function empty(): self
    {
        return new self(
            id: null,
            status: 'empty',
            expiresAt: null,
            items: [],
            totalAmount: 0,
            currency: '',
        );
    }
}
