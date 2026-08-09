<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

use Domain\Event\ValueObject\TicketCategoryId;

final class CartItemNotFound extends CartException
{
    public static function forCategory(TicketCategoryId $categoryId): self
    {
        return new self(sprintf('No item for ticket category "%s" in this cart.', $categoryId->toString()));
    }
}
