<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Order\ValueObject\OrderId;

final class OrderNotFound extends OrderException
{
    public static function forId(OrderId $id): self
    {
        return new self(sprintf('Order "%s" not found.', $id->toString()));
    }
}
