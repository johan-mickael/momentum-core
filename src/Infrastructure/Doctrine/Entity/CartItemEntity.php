<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'cart_item')]
#[ORM\UniqueConstraint(name: 'uniq_cart_item_category', columns: ['cart_id', 'category_id'])]
class CartItemEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: CartEntity::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'cart_id', nullable: false, onDelete: 'CASCADE')]
    private CartEntity $cart;

    #[ORM\Column(name: 'category_id', type: 'guid')]
    private string $categoryId;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(name: 'added_at')]
    private \DateTimeImmutable $addedAt;

    public function __construct(
        CartEntity $cart,
        string $categoryId,
        int $quantity,
        \DateTimeImmutable $addedAt,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->cart = $cart;
        $this->categoryId = $categoryId;
        $this->quantity = $quantity;
        $this->addedAt = $addedAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCart(): CartEntity
    {
        return $this->cart;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
