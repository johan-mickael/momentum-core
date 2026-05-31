<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Domain\Ticket\TicketStatus;

#[ORM\Entity]
#[ORM\Table(name: 'ticket')]
#[ORM\Index(name: 'idx_ticket_event_id', columns: ['event_id'])]
#[ORM\Index(name: 'idx_ticket_holder_id', columns: ['holder_id'])]
#[ORM\UniqueConstraint(name: 'uniq_ticket_entry_code', columns: ['entry_code'])]
class TicketEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(type: 'guid', name: 'event_id')]
    private string $eventId;

    #[ORM\Column(type: 'guid', name: 'category_id')]
    private string $categoryId;

    #[ORM\Column(type: 'guid', name: 'holder_id')]
    private string $holderId;

    #[ORM\Column(type: 'integer')]
    private int $priceAmount;

    #[ORM\Column(length: 3)]
    private string $priceCurrency;

    #[ORM\Column(enumType: TicketStatus::class)]
    private TicketStatus $status;

    #[ORM\Column]
    private \DateTimeImmutable $reservedAt;

    #[ORM\Column(length: 36, unique: true, name: 'entry_code')]
    private string $entryCode;

    public function __construct(
        string $id,
        string $eventId,
        string $categoryId,
        string $holderId,
        int $priceAmount,
        string $priceCurrency,
        TicketStatus $status,
        \DateTimeImmutable $reservedAt,
        string $entryCode,
    ) {
        $this->id = $id;
        $this->eventId = $eventId;
        $this->categoryId = $categoryId;
        $this->holderId = $holderId;
        $this->priceAmount = $priceAmount;
        $this->priceCurrency = $priceCurrency;
        $this->status = $status;
        $this->reservedAt = $reservedAt;
        $this->entryCode = $entryCode;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function getHolderId(): string
    {
        return $this->holderId;
    }

    public function getPriceAmount(): int
    {
        return $this->priceAmount;
    }

    public function getPriceCurrency(): string
    {
        return $this->priceCurrency;
    }

    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): void
    {
        $this->status = $status;
    }

    public function getReservedAt(): \DateTimeImmutable
    {
        return $this->reservedAt;
    }

    public function getEntryCode(): string
    {
        return $this->entryCode;
    }
}
