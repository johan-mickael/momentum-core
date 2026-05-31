<?php

declare(strict_types=1);

namespace Domain\Ticket;

use Domain\Common\ValueObject\PaginatedList;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Event\ValueObject\EventId;
use Domain\Ticket\ValueObject\EntryCode;
use Domain\Ticket\ValueObject\TicketId;
use Domain\User\ValueObject\UserId;

interface TicketRepositoryInterface
{
    public function findById(TicketId $id): Ticket;

    public function findByEntryCode(EntryCode $entryCode): Ticket;

    /** @return Ticket[] */
    public function findAllByEvent(EventId $eventId): array;

    public function findByEvent(
        EventId $eventId,
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?TicketStatus $statusFilter = null,
    ): PaginatedList;

    public function findByHolder(
        UserId $holderId,
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?TicketStatus $statusFilter = null,
    ): PaginatedList;

    public function save(Ticket $ticket): void;
}
