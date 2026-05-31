<?php

declare(strict_types=1);

namespace Application\Order\Result;

final readonly class CheckoutResult
{
    public function __construct(
        public string $checkoutUrl,
    ) {
    }
}
