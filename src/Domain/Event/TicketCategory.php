<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\Exception\CannotReserveTicketInCategory;
use App\Domain\Event\Exception\TicketCategoryCapacityInvalid;
use App\Domain\Event\Exception\TicketCategoryNameEmpty;
use App\Domain\Event\ValueObject\TicketCategoryId;

final class TicketCategory
{
    private int $reservedQuantity = 0;

    private function __construct(
        private readonly TicketCategoryId $id,
        private string $name,
        private Money $price,
        private int $totalQuantity,
    ) {
        $this->assertValidName($name);
        $this->assertValidCapacity($totalQuantity);
    }

    public static function create(
        TicketCategoryId $id,
        string $name,
        Money $price,
        int $totalQuantity,
    ): self {
        return new self(
            id: $id,
            name: $name,
            price: $price,
            totalQuantity: $totalQuantity,
        );
    }

    public function reserveOneTicket(): void
    {
        if ($this->availableQuantity() === 0) {
            throw CannotReserveTicketInCategory::noAvailability($this->name);
        }

        $this->reservedQuantity++;
    }

    public function releaseOneTicket(): void
    {
        if ($this->reservedQuantity === 0) {
            return;
        }

        $this->reservedQuantity--;
    }

    public function availableQuantity(): int
    {
        return $this->totalQuantity - $this->reservedQuantity;
    }

    public function isSoldOut(): bool
    {
        return $this->availableQuantity() === 0;
    }

    public function id(): TicketCategoryId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function totalQuantity(): int
    {
        return $this->totalQuantity;
    }

    public function reservedQuantity(): int
    {
        return $this->reservedQuantity;
    }

    private function assertValidName(string $name): void
    {
        if (trim($name) === '') {
            throw TicketCategoryNameEmpty::create();
        }
    }

    private function assertValidCapacity(int $totalQuantity): void
    {
        if ($totalQuantity < 1) {
            throw TicketCategoryCapacityInvalid::create($totalQuantity);
        }
    }
}
