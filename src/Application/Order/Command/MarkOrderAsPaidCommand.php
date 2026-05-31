<?php

declare(strict_types=1);

namespace Application\Order\Command;

final readonly class MarkOrderAsPaidCommand
{
    public function __construct(
        public string $orderId,
        public string $paidAt,
        public ?string $paymentReference = null,
    ) {
    }
}
