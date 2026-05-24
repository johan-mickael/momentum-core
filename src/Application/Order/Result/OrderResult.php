<?php

declare(strict_types=1);

namespace App\Application\Order\Result;

final readonly class OrderResult
{
    /** @param OrderItemResult[] $items */
    public function __construct(
        public string  $id,
        public string  $status,
        public string  $buyerId,
        public string  $eventId,
        public int     $totalAmount,
        public string  $currency,
        public string  $placedAt,
        public string  $expiresAt,
        public ?string $paidAt,
        public ?string $cancelledAt,
        public array   $items,
    ) {}
}
