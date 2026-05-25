<?php

declare(strict_types=1);

namespace App\Domain\Ticket;

use App\Domain\Event\ValueObject\EventId;
use App\Domain\Ticket\ValueObject\EntryCode;
use App\Domain\Ticket\ValueObject\TicketId;
use App\Domain\User\ValueObject\UserId;

interface TicketRepositoryInterface
{
    public function findById(TicketId $id): Ticket;

    public function findByEntryCode(EntryCode $entryCode): Ticket;

    /** @return Ticket[] */
    public function findByEvent(EventId $eventId): array;

    /** @return Ticket[] */
    public function findByHolder(UserId $holderId): array;

    public function save(Ticket $ticket): void;
}
