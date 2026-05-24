<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Domain\Order\OrderStatus;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '"order"')]
#[ORM\Index(name: 'idx_order_buyer_id', columns: ['buyer_id'])]
#[ORM\Index(name: 'idx_order_status_expires_at', columns: ['status', 'expires_at'])]
class OrderEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(type: 'guid', name: 'buyer_id')]
    private string $buyerId;

    #[ORM\Column(type: 'guid', name: 'event_id')]
    private string $eventId;

    #[ORM\Column(enumType: OrderStatus::class)]
    private OrderStatus $status;

    #[ORM\Column(type: 'integer', name: 'total_amount')]
    private int $totalAmount;

    #[ORM\Column(length: 3, name: 'total_currency')]
    private string $totalCurrency;

    #[ORM\Column(name: 'placed_at')]
    private DateTimeImmutable $placedAt;

    #[ORM\Column(name: 'expires_at')]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'paid_at', nullable: true)]
    private ?DateTimeImmutable $paidAt;

    #[ORM\Column(name: 'cancelled_at', nullable: true)]
    private ?DateTimeImmutable $cancelledAt;

    #[ORM\OneToMany(
        targetEntity: OrderItemEntity::class,
        mappedBy: 'order',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $items;

    public function __construct(
        string $id,
        string $buyerId,
        string $eventId,
        OrderStatus $status,
        int $totalAmount,
        string $totalCurrency,
        DateTimeImmutable $placedAt,
        DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $paidAt = null,
        ?DateTimeImmutable $cancelledAt = null,
    ) {
        $this->id = $id;
        $this->buyerId = $buyerId;
        $this->eventId = $eventId;
        $this->status = $status;
        $this->totalAmount = $totalAmount;
        $this->totalCurrency = $totalCurrency;
        $this->placedAt = $placedAt;
        $this->expiresAt = $expiresAt;
        $this->paidAt = $paidAt;
        $this->cancelledAt = $cancelledAt;
        $this->items = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getBuyerId(): string
    {
        return $this->buyerId;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function getTotalCurrency(): string
    {
        return $this->totalCurrency;
    }

    public function getPlacedAt(): DateTimeImmutable
    {
        return $this->placedAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function setStatus(OrderStatus $status): void
    {
        $this->status = $status;
    }

    public function setPaidAt(?DateTimeImmutable $paidAt): void
    {
        $this->paidAt = $paidAt;
    }

    public function setCancelledAt(?DateTimeImmutable $at): void
    {
        $this->cancelledAt = $at;
    }

    public function addItem(OrderItemEntity $item): void
    {
        $this->items->add($item);
    }
}
