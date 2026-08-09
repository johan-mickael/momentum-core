<?php

declare(strict_types=1);

namespace Domain\Cart;

use Domain\Cart\ValueObject\CartId;
use Domain\Event\ValueObject\EventId;
use Domain\User\ValueObject\UserId;

interface CartRepositoryInterface
{
    public function findById(CartId $id): Cart;

    /**
     * Loads the cart with an exclusive row lock, serializing concurrent
     * mutations (e.g. quantity changes racing an expiry sweep) against the
     * same cart. Must be called inside a transaction.
     */
    public function findByIdForUpdate(CartId $id): Cart;

    public function findActiveByBuyerAndEvent(UserId $buyerId, EventId $eventId): ?Cart;

    /** @return Cart[] all active carts for the given event (e.g. to release on event cancellation) */
    public function findActiveByEvent(EventId $eventId): array;

    /** @return Cart[] all active carts for the given buyer, across every event they're shopping for */
    public function findAllActiveByBuyer(UserId $buyerId): array;

    /**
     * Same as findActiveByBuyerAndEvent but with an exclusive row lock, so
     * add/update/remove/checkout can never race the expiry sweep on the
     * same cart. Must be called inside a transaction.
     */
    public function findActiveByBuyerAndEventForUpdate(UserId $buyerId, EventId $eventId): ?Cart;

    /** @return Cart[] active carts whose TTL has already lapsed */
    public function findExpired(\DateTimeImmutable $before, int $limit = 100): array;

    public function save(Cart $cart): void;
}
