<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class CancelOrderForbidden extends OrderException
{
    public static function create(): self
    {
        return new self('You are not allowed to cancel this order.');
    }
}
