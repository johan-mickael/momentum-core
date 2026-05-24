<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'order_item')]
class OrderItemEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: OrderEntity::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'order_id', nullable: false, onDelete: 'CASCADE')]
    private OrderEntity $order;

    #[ORM\Column(name: 'category_id', type: 'guid')]
    private string $categoryId;

    #[ORM\Column(name: 'ticket_id', type: 'guid', unique: true)]
    private string $ticketId;

    #[ORM\Column(name: 'unit_price_amount', type: 'integer')]
    private int $unitPriceAmount;

    #[ORM\Column(name: 'unit_price_currency', length: 3)]
    private string $unitPriceCurrency;

    public function __construct(
        OrderEntity $order,
        string $categoryId,
        string $ticketId,
        int $unitPriceAmount,
        string $unitPriceCurrency,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->order = $order;
        $this->categoryId = $categoryId;
        $this->ticketId = $ticketId;
        $this->unitPriceAmount = $unitPriceAmount;
        $this->unitPriceCurrency = $unitPriceCurrency;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function getTicketId(): string
    {
        return $this->ticketId;
    }

    public function getUnitPriceAmount(): int
    {
        return $this->unitPriceAmount;
    }

    public function getUnitPriceCurrency(): string
    {
        return $this->unitPriceCurrency;
    }
}
