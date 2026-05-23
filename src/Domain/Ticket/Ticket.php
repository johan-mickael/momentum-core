<?php

declare(strict_types=1);

namespace App\Domain\Ticket;

use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Ticket\Exception\InvalidTicketStatusTransition;
use App\Domain\Ticket\ValueObject\TicketId;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;

final class Ticket
{
    private function __construct(
        private readonly TicketId $id,
        private readonly EventId $eventId,
        private readonly TicketCategoryId $categoryId,
        private readonly UserId $holderId,
        private readonly Money $price,
        private TicketStatus $status,
        private readonly DateTimeImmutable $reservedAt,
    ) {}

    public static function reserve(
        TicketId $id,
        EventId $eventId,
        TicketCategoryId $categoryId,
        UserId $holderId,
        Money $price,
        DateTimeImmutable $reservedAt,
    ): self {
        return new self(
            id: $id,
            eventId: $eventId,
            categoryId: $categoryId,
            holderId: $holderId,
            price: $price,
            status: TicketStatus::RESERVED,
            reservedAt: $reservedAt,
        );
    }

    public function confirm(): void
    {
        if ($this->status !== TicketStatus::RESERVED) {
            throw InvalidTicketStatusTransition::cannotConfirm($this->status);
        }

        $this->status = TicketStatus::CONFIRMED;
    }

    public function cancel(): void
    {
        if ($this->status === TicketStatus::CANCELLED) {
            throw InvalidTicketStatusTransition::cannotCancel($this->status);
        }

        if ($this->status === TicketStatus::USED) {
            throw InvalidTicketStatusTransition::cannotCancel($this->status);
        }

        $this->status = TicketStatus::CANCELLED;
    }

    public function markAsUsed(): void
    {
        if ($this->status !== TicketStatus::CONFIRMED) {
            throw InvalidTicketStatusTransition::cannotMarkAsUsed($this->status);
        }

        $this->status = TicketStatus::USED;
    }

    public static function reconstitute(
        TicketId $id,
        EventId $eventId,
        TicketCategoryId $categoryId,
        UserId $holderId,
        Money $price,
        TicketStatus $status,
        DateTimeImmutable $reservedAt,
    ): self {
        return new self(
            id: $id,
            eventId: $eventId,
            categoryId: $categoryId,
            holderId: $holderId,
            price: $price,
            status: $status,
            reservedAt: $reservedAt,
        );
    }

    public function id(): TicketId { return $this->id; }
    public function eventId(): EventId { return $this->eventId; }
    public function categoryId(): TicketCategoryId { return $this->categoryId; }
    public function holderId(): UserId { return $this->holderId; }
    public function price(): Money { return $this->price; }
    public function status(): TicketStatus { return $this->status; }
    public function reservedAt(): DateTimeImmutable { return $this->reservedAt; }
}
