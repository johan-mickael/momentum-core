<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Event\ValueObject\EventId;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;

interface OrderRepositoryInterface
{
    public function getById(OrderId $id): Order;

    /** @return Order[] */
    public function findPendingExpiredBefore(DateTimeImmutable $expiresAt): array;

    /** @return Order[] */
    public function findPendingByEventId(EventId $eventId): array;

    /** @return Order[] */
    public function findByBuyerId(UserId $buyerId): array;

    public function save(Order $order): void;
}
