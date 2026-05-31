<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ticket_category')]
class TicketCategoryEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: EventEntity::class, inversedBy: 'ticketCategories')]
    #[ORM\JoinColumn(name: 'event_id', nullable: false)]
    private EventEntity $event;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $priceAmount;

    #[ORM\Column(length: 3)]
    private string $priceCurrency;

    #[ORM\Column(type: 'integer')]
    private int $totalQuantity;

    #[ORM\Column(type: 'integer')]
    private int $reservedQuantity;

    public function __construct(
        string $id,
        EventEntity $event,
        string $name,
        int $priceAmount,
        string $priceCurrency,
        int $totalQuantity,
        int $reservedQuantity = 0,
    ) {
        $this->id = $id;
        $this->event = $event;
        $this->name = $name;
        $this->priceAmount = $priceAmount;
        $this->priceCurrency = $priceCurrency;
        $this->totalQuantity = $totalQuantity;
        $this->reservedQuantity = $reservedQuantity;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEvent(): EventEntity
    {
        return $this->event;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPriceAmount(): int
    {
        return $this->priceAmount;
    }

    public function setPriceAmount(int $priceAmount): void
    {
        $this->priceAmount = $priceAmount;
    }

    public function getPriceCurrency(): string
    {
        return $this->priceCurrency;
    }

    public function setPriceCurrency(string $priceCurrency): void
    {
        $this->priceCurrency = $priceCurrency;
    }

    public function getTotalQuantity(): int
    {
        return $this->totalQuantity;
    }

    public function setTotalQuantity(int $totalQuantity): void
    {
        $this->totalQuantity = $totalQuantity;
    }

    public function getReservedQuantity(): int
    {
        return $this->reservedQuantity;
    }

    public function setReservedQuantity(int $reservedQuantity): void
    {
        $this->reservedQuantity = $reservedQuantity;
    }
}
