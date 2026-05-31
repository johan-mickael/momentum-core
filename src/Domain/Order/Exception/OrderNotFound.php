<?php

declare(strict_types=1);

namespace Domain\Order\Exception;

use Domain\Order\ValueObject\OrderId;

final class OrderNotFound extends OrderException
{
    public static function forId(OrderId $id): self
    {
        return new self(sprintf('Order "%s" not found.', $id->toString()));
    }
}
