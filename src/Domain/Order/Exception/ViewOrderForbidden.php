<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Order\ValueObject\OrderId;

final class ViewOrderForbidden extends OrderException
{
    public static function forOrder(OrderId $id): self
    {
        return new self(sprintf('You are not allowed to view order "%s".', $id->toString()));
    }
}
