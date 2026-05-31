<?php

declare(strict_types=1);

namespace Application\Ticket\Result;

final readonly class ReserveTicketResult
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
