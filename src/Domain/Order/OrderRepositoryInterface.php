<?php

declare(strict_types=1);

namespace Domain\Order;

use Domain\Common\ValueObject\PaginatedList;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Event\ValueObject\EventId;
use Domain\Order\ValueObject\OrderId;
use Domain\User\ValueObject\UserId;

interface OrderRepositoryInterface
{
    public function getById(OrderId $id): Order;

    /** @return Order[] */
    public function findPendingExpiredBefore(\DateTimeImmutable $expiresAt): array;

    /** @return Order[] */
    public function findPendingByEventId(EventId $eventId): array;

    public function findByBuyerId(
        UserId $buyerId,
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?OrderStatus $statusFilter = null,
    ): PaginatedList;

    public function save(Order $order): void;
}
