<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Order\OrderStatus;
use App\Domain\Order\ValueObject\OrderId;

final class CannotRefundOrder extends \DomainException
{
    public static function notPaid(OrderId $id, OrderStatus $status): self
    {
        return new self(sprintf(
            'Order "%s" cannot be refunded — current status is "%s"',
            $id->toString(),
            $status->value,
        ));
    }
}
