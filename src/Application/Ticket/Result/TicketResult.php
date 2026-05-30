<?php

declare(strict_types=1);

namespace App\Application\Ticket\Result;

final readonly class TicketResult
{
    public function __construct(
        public string $id,
        public string $eventId,
        public string $categoryId,
        public string $holderId,
        public int $priceAmount,
        public string $priceCurrency,
        public string $status,
        public string $reservedAt,
        public string $entryCode,
    ) {
    }
}
