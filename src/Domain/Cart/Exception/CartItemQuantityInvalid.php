<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

final class CartItemQuantityInvalid extends CartException
{
    public static function notPositive(int $quantity): self
    {
        return new self(sprintf('Cart item quantity must be a positive integer, got %d.', $quantity));
    }

    public static function exceedsLimitPerCategory(int $quantity, int $max): self
    {
        return new self(sprintf(
            'Cart item quantity %d exceeds the maximum of %d per ticket category.',
            $quantity,
            $max,
        ));
    }
}
