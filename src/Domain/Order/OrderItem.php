<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Ticket\ValueObject\TicketId;

final readonly class OrderItem
{
    public function __construct(
        private TicketCategoryId $categoryId,
        private TicketId $ticketId,
        private Money $unitPrice,
    ) {
    }

    public function categoryId(): TicketCategoryId
    {
        return $this->categoryId;
    }

    public function ticketId(): TicketId
    {
        return $this->ticketId;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }
}
