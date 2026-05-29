<?php

declare(strict_types=1);

namespace App\Domain\Ticket;

use App\Domain\Common\ValueObject\PaginatedList;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Ticket\ValueObject\EntryCode;
use App\Domain\Ticket\ValueObject\TicketId;
use App\Domain\User\ValueObject\UserId;

interface TicketRepositoryInterface
{
    public function findById(TicketId $id): Ticket;

    public function findByEntryCode(EntryCode $entryCode): Ticket;

    public function findByEvent(
        EventId            $eventId,
        PaginationCriteria $pagination,
        SortCriteria       $sort,
        ?TicketStatus      $statusFilter = null,
    ): PaginatedList;

    public function findByHolder(
        UserId             $holderId,
        PaginationCriteria $pagination,
        SortCriteria       $sort,
        ?TicketStatus      $statusFilter = null,
    ): PaginatedList;

    public function save(Ticket $ticket): void;
}
