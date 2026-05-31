<?php

declare(strict_types=1);

namespace Domain\Order\Exception;

final class CheckoutForbidden extends OrderException
{
    public static function create(): self
    {
        return new self('You are not allowed to initiate checkout for this order.');
    }
}
