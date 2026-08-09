<?php

declare(strict_types=1);

namespace Application\Cart\Result;

final readonly class MyCartSummaryResult
{
    public function __construct(
        public string $eventId,
        public string $eventName,
        public CartResult $cart,
    ) {
    }
}
