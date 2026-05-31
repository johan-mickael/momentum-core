<?php

declare(strict_types=1);

namespace Application\Order\Command;

final readonly class RefundOrderCommand
{
    public function __construct(
        public string $orderId,
    ) {
    }
}
