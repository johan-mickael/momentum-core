<?php

declare(strict_types=1);

namespace App\Application\Order\Exception;

use App\Domain\Order\ValueObject\OrderId;

final class OrderHasNoPaymentReference extends \DomainException
{
    public static function forOrder(OrderId $id): self
    {
        return new self(sprintf(
            'Order "%s" has no payment reference — cannot issue a refund',
            $id->toString(),
        ));
    }
}
