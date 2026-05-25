<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Common\ValueObject\Currency;
use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\ValueObject\EntryCode;
use App\Domain\Ticket\ValueObject\TicketId;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\Persistence\Doctrine\Entity\TicketEntity;

final class TicketMapper
{
    public function toDomain(TicketEntity $entity): Ticket
    {
        return Ticket::reconstitute(
            id: TicketId::fromString($entity->getId()),
            eventId: EventId::fromString($entity->getEventId()),
            categoryId: TicketCategoryId::fromString($entity->getCategoryId()),
            holderId: UserId::fromString($entity->getHolderId()),
            price: new Money($entity->getPriceAmount(), Currency::fromString($entity->getPriceCurrency())),
            status: $entity->getStatus(),
            reservedAt: $entity->getReservedAt(),
            entryCode: EntryCode::fromString($entity->getEntryCode()),
        );
    }

    public function toEntity(Ticket $ticket): TicketEntity
    {
        return new TicketEntity(
            id: $ticket->id()->toString(),
            eventId: (string) $ticket->eventId(),
            categoryId: $ticket->categoryId()->toString(),
            holderId: (string) $ticket->holderId(),
            priceAmount: $ticket->price()->amount(),
            priceCurrency: $ticket->price()->currency()->toString(),
            status: $ticket->status(),
            reservedAt: $ticket->reservedAt(),
            entryCode: $ticket->entryCode()->toString(),
        );
    }

    public function updateEntity(TicketEntity $entity, Ticket $ticket): void
    {
        $entity->setStatus($ticket->status());
    }
}
