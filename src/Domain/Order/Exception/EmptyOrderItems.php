<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class EmptyOrderItems extends OrderException
{
    public static function create(): self
    {
        return new self('An order must contain at least one item.');
    }
}
