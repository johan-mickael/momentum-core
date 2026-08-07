<?php

declare(strict_types=1);

namespace Domain\Event;

use Domain\Common\ValueObject\PaginatedList;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\EventVisibility;

interface EventRepositoryInterface
{
    public function create(Event $event): void;

    public function save(Event $event): void;

    public function getById(EventId $id): Event;

    /**
     * Loads the aggregate with an exclusive row lock, serializing concurrent
     * mutations (e.g. ticket reservations) against the same event. Must be
     * called inside a transaction.
     */
    public function getByIdForUpdate(EventId $id): Event;

    public function remove(Event $event): void;

    public function list(
        EventVisibility $visibility,
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?EventStatus $statusFilter = null,
    ): PaginatedList;
}
