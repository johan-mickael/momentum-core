<?php

declare(strict_types=1);

namespace App\Application\Order\Command;

final readonly class CancelOrderCommand
{
    public function __construct(
        public string $orderId,
    ) {
    }
}
