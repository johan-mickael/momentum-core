<?php

declare(strict_types=1);

namespace Domain\Order\Exception;

final class InvalidOrderId extends OrderException
{
    public static function invalidUuid(string $value): self
    {
        return new self("Invalid order id: $value");
    }
}
