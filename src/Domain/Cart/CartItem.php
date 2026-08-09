<?php

declare(strict_types=1);

namespace Domain\Cart;

use Domain\Event\ValueObject\TicketCategoryId;

final readonly class CartItem
{
    public function __construct(
        private TicketCategoryId $categoryId,
        private int $quantity,
        private \DateTimeImmutable $addedAt,
    ) {
    }

    public function categoryId(): TicketCategoryId
    {
        return $this->categoryId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function addedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
