<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Common\ValueObject\PaginatedList;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\EventVisibility;

interface EventRepositoryInterface
{
    public function create(Event $event): void;

    public function save(Event $event): void;

    public function getById(EventId $id): Event;

    public function remove(Event $event): void;

    public function list(
        EventVisibility $visibility,
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?EventStatus $statusFilter = null,
    ): PaginatedList;
}
