<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Order\ValueObject\OrderId;

final class OrderAlreadyPaid extends OrderException
{
    public static function forOrder(OrderId $id): self
    {
        return new self(sprintf('Order "%s" has already been paid.', $id->toString()));
    }
}
