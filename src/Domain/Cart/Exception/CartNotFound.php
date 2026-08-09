<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

use Domain\Cart\ValueObject\CartId;
use Domain\Event\ValueObject\EventId;
use Domain\User\ValueObject\UserId;

final class CartNotFound extends CartException
{
    public static function forId(CartId $id): self
    {
        return new self(sprintf('Cart "%s" not found.', $id->toString()));
    }

    public static function forBuyerAndEvent(UserId $buyerId, EventId $eventId): self
    {
        return new self(sprintf(
            'No active cart for buyer "%s" and event "%s".',
            (string) $buyerId,
            (string) $eventId,
        ));
    }
}
