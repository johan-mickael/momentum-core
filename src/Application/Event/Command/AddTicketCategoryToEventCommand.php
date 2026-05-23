<?php

declare(strict_types=1);

namespace App\Application\Event\Command;

final class AddTicketCategoryToEventCommand
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $name,
        public readonly int $priceAmount,
        public readonly string $priceCurrency,
        public readonly int $totalQuantity,
    ) {}
}
