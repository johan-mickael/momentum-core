<?php

declare(strict_types=1);

namespace Domain\Order;

use Domain\Common\ValueObject\Money;
use Domain\Event\ValueObject\TicketCategoryId;
use Domain\Ticket\ValueObject\TicketId;

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
