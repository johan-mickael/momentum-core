<?php

declare(strict_types=1);

namespace Application\Event\Result;

final readonly class TicketCategoryResult
{
    public function __construct(
        public string $id,
        public string $name,
        public int $priceAmount,
        public string $priceCurrency,
        public int $totalQuantity,
        public int $availableQuantity,
    ) {
    }
}
