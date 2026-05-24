<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Order\ValueObject\OrderId;

final class CannotPayCancelledOrder extends OrderException
{
    public static function forOrder(OrderId $id): self
    {
        return new self(sprintf('Cannot pay order "%s" because it has been cancelled.', $id->toString()));
    }
}
