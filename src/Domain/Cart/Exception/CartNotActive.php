<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

use Domain\Cart\CartStatus;
use Domain\Cart\ValueObject\CartId;

final class CartNotActive extends CartException
{
    public static function forCart(CartId $id, CartStatus $status): self
    {
        return new self(sprintf(
            'Cart "%s" is "%s" and can no longer be modified.',
            $id->toString(),
            $status->value,
        ));
    }
}
