<?php

declare(strict_types=1);

namespace Domain\Ticket;

use Domain\Common\Event\RecordsDomainEvents;
use Domain\Common\ValueObject\Money;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;
use Domain\Ticket\Event\TicketCancelled;
use Domain\Ticket\Event\TicketConfirmed;
use Domain\Ticket\Event\TicketReserved;
use Domain\Ticket\Event\TicketUsed;
use Domain\Ticket\Exception\InvalidTicketStatusTransition;
use Domain\Ticket\ValueObject\EntryCode;
use Domain\Ticket\ValueObject\TicketId;
use Domain\User\ValueObject\UserId;

final class Ticket
{
    use RecordsDomainEvents;

    private function __construct(
        private readonly TicketId $id,
        private readonly EventId $eventId,
        private readonly TicketCategoryId $categoryId,
        private readonly UserId $holderId,
        private readonly Money $price,
        private TicketStatus $status,
        private readonly \DateTimeImmutable $reservedAt,
        private readonly EntryCode $entryCode,
    ) {
    }

    public static function reserve(
        TicketId $id,
        EventId $eventId,
        TicketCategoryId $categoryId,
        UserId $holderId,
        Money $price,
        \DateTimeImmutable $reservedAt,
        EntryCode $entryCode,
    ): self {
        $ticket = new self(
            id: $id,
            eventId: $eventId,
            categoryId: $categoryId,
            holderId: $holderId,
            price: $price,
            status: TicketStatus::RESERVED,
            reservedAt: $reservedAt,
            entryCode: $entryCode,
        );

        $ticket->recordEvent(new TicketReserved(
            ticketId: $id->toString(),
            eventId: (string) $eventId,
            categoryId: $categoryId->toString(),
            holderId: (string) $holderId,
        ));

        return $ticket;
    }

    public function confirm(): void
    {
        if (TicketStatus::RESERVED !== $this->status) {
            throw InvalidTicketStatusTransition::cannotConfirm($this->status);
        }

        $this->status = TicketStatus::CONFIRMED;

        $this->recordEvent(new TicketConfirmed(
            ticketId: $this->id->toString(),
            eventId: (string) $this->eventId,
            categoryId: $this->categoryId->toString(),
            holderId: (string) $this->holderId,
        ));
    }

    public function cancel(): void
    {
        if (TicketStatus::CANCELLED === $this->status) {
            throw InvalidTicketStatusTransition::cannotCancel($this->status);
        }

        if (TicketStatus::USED === $this->status) {
            throw InvalidTicketStatusTransition::cannotCancel($this->status);
        }

        $this->status = TicketStatus::CANCELLED;

        $this->recordEvent(new TicketCancelled(
            ticketId: $this->id->toString(),
            eventId: (string) $this->eventId,
            categoryId: $this->categoryId->toString(),
        ));
    }

    public function markAsUsed(): void
    {
        if (TicketStatus::CONFIRMED !== $this->status) {
            throw InvalidTicketStatusTransition::cannotMarkAsUsed($this->status);
        }

        $this->status = TicketStatus::USED;

        $this->recordEvent(new TicketUsed(
            ticketId: $this->id->toString(),
            eventId: (string) $this->eventId,
        ));
    }

    public static function reconstitute(
        TicketId $id,
        EventId $eventId,
        TicketCategoryId $categoryId,
        UserId $holderId,
        Money $price,
        TicketStatus $status,
        \DateTimeImmutable $reservedAt,
        EntryCode $entryCode,
    ): self {
        return new self(
            id: $id,
            eventId: $eventId,
            categoryId: $categoryId,
            holderId: $holderId,
            price: $price,
            status: $status,
            reservedAt: $reservedAt,
            entryCode: $entryCode,
        );
    }

    public function id(): TicketId
    {
        return $this->id;
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function categoryId(): TicketCategoryId
    {
        return $this->categoryId;
    }

    public function holderId(): UserId
    {
        return $this->holderId;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function status(): TicketStatus
    {
        return $this->status;
    }

    public function reservedAt(): \DateTimeImmutable
    {
        return $this->reservedAt;
    }

    public function entryCode(): EntryCode
    {
        return $this->entryCode;
    }
}
