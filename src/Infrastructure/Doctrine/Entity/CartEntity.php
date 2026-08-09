<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Cart\CartStatus;

#[ORM\Entity]
#[ORM\Table(name: 'cart')]
#[ORM\Index(name: 'idx_cart_status_expires_at', columns: ['status', 'expires_at'])]
#[ORM\Index(name: 'idx_cart_buyer_event', columns: ['buyer_id', 'event_id'])]
// One ACTIVE cart per (buyer, event) is enforced by a partial unique index
// in the migration — Doctrine attributes can't express a WHERE-filtered
// unique constraint, so it's raw SQL rather than #[ORM\UniqueConstraint].
class CartEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(type: 'guid', name: 'buyer_id')]
    private string $buyerId;

    #[ORM\Column(type: 'guid', name: 'event_id')]
    private string $eventId;

    #[ORM\Column(enumType: CartStatus::class)]
    private CartStatus $status;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'expires_at')]
    private \DateTimeImmutable $expiresAt;

    /** @var Collection<int, CartItemEntity> */
    #[ORM\OneToMany(
        targetEntity: CartItemEntity::class,
        mappedBy: 'cart',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $items;

    public function __construct(
        string $id,
        string $buyerId,
        string $eventId,
        CartStatus $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $expiresAt,
    ) {
        $this->id = $id;
        $this->buyerId = $buyerId;
        $this->eventId = $eventId;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
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

    public function getStatus(): CartStatus
    {
        return $this->status;
    }

    public function setStatus(CartStatus $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    /** @return Collection<int, CartItemEntity> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CartItemEntity $item): void
    {
        $this->items->add($item);
    }

    public function removeItem(CartItemEntity $item): void
    {
        $this->items->removeElement($item);
    }
}
