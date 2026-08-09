<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

final class CheckoutCartForbidden extends CartException
{
    public static function create(): self
    {
        return new self('You are not allowed to check out this cart.');
    }
}
