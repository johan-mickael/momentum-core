<?php

declare(strict_types=1);

namespace Application\Order\Exception;

use Domain\Order\ValueObject\OrderId;

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
