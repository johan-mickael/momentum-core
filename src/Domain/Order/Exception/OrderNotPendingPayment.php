<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Order\ValueObject\OrderId;

final class OrderNotPendingPayment extends OrderException
{
    public static function forOrder(OrderId $id): self
    {
        return new self(sprintf('Order "%s" is not pending payment.', $id->toString()));
    }
}
