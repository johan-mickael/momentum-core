<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

use Domain\Cart\ValueObject\CartId;

final class CartAlreadyExpired extends CartException
{
    public static function forCart(CartId $id): self
    {
        return new self(sprintf('Cart "%s" has expired; its held tickets were released.', $id->toString()));
    }
}
