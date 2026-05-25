<?php

declare(strict_types=1);

namespace App\Application\Order\Command;

final readonly class RefundOrderCommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
