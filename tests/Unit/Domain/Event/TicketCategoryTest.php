<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event;

use App\Domain\Common\ValueObject\Currency;
use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\Exception\CannotReserveTicketInCategory;
use App\Domain\Event\Exception\TicketCategoryCapacityInvalid;
use App\Domain\Event\Exception\TicketCategoryNameEmpty;
use App\Domain\Event\TicketCategory;
use App\Domain\Event\ValueObject\TicketCategoryId;
use PHPUnit\Framework\TestCase;

final class TicketCategoryTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';

    private TicketCategoryId $id;
    private Money $price;

    protected function setUp(): void
    {
        $this->id = TicketCategoryId::fromString(self::VALID_UUID);
        $this->price = new Money(2500, Currency::fromString('EUR'));
    }

    public function testCreateWithValidData(): void
    {
        $category = TicketCategory::create(
            id: $this->id,
            name: 'VIP',
            price: $this->price,
            totalQuantity: 100,
        );

        self::assertSame($this->id, $category->id());
        self::assertSame('VIP', $category->name());
        self::assertTrue($category->price()->equals($this->price));
        self::assertSame(100, $category->totalQuantity());
        self::assertSame(0, $category->reservedQuantity());
        self::assertSame(100, $category->availableQuantity());
    }

    public function testCreateWithEmptyNameThrows(): void
    {
        $this->expectException(TicketCategoryNameEmpty::class);

        TicketCategory::create(
            id: $this->id,
            name: '',
            price: $this->price,
            totalQuantity: 100,
        );
    }

    public function testCreateWithWhitespaceOnlyNameThrows(): void
    {
        $this->expectException(TicketCategoryNameEmpty::class);

        TicketCategory::create(
            id: $this->id,
            name: '   ',
            price: $this->price,
            totalQuantity: 100,
        );
    }

    public function testCreateWithZeroCapacityThrows(): void
    {
        $this->expectException(TicketCategoryCapacityInvalid::class);

        TicketCategory::create(
            id: $this->id,
            name: 'VIP',
            price: $this->price,
            totalQuantity: 0,
        );
    }

    public function testCreateWithNegativeCapacityThrows(): void
    {
        $this->expectException(TicketCategoryCapacityInvalid::class);

        TicketCategory::create(
            id: $this->id,
            name: 'VIP',
            price: $this->price,
            totalQuantity: -1,
        );
    }

    public function testCreateWithMinimumCapacityOfOneSucceeds(): void
    {
        $category = TicketCategory::create(
            id: $this->id,
            name: 'VIP',
            price: $this->price,
            totalQuantity: 1,
        );

        self::assertSame(1, $category->totalQuantity());
    }

    public function testReserveOneTicketDecrementsAvailability(): void
    {
        $category = $this->makeCategory(totalQuantity: 3);

        $category->reserveOneTicket();

        self::assertSame(1, $category->reservedQuantity());
        self::assertSame(2, $category->availableQuantity());
    }

    public function testReserveMultipleTicketsTracksCorrectly(): void
    {
        $category = $this->makeCategory(totalQuantity: 5);

        $category->reserveOneTicket();
        $category->reserveOneTicket();
        $category->reserveOneTicket();

        self::assertSame(3, $category->reservedQuantity());
        self::assertSame(2, $category->availableQuantity());
    }

    public function testReserveWhenSoldOutThrows(): void
    {
        $category = $this->makeCategory(totalQuantity: 1);
        $category->reserveOneTicket();

        $this->expectException(CannotReserveTicketInCategory::class);

        $category->reserveOneTicket();
    }

    public function testReleaseOneTicketIncrementsAvailability(): void
    {
        $category = $this->makeCategory(totalQuantity: 3);
        $category->reserveOneTicket();
        $category->reserveOneTicket();

        $category->releaseOneTicket();

        self::assertSame(1, $category->reservedQuantity());
        self::assertSame(2, $category->availableQuantity());
    }

    public function testReleaseOneTicketWhenNoneReservedIsIdempotent(): void
    {
        $category = $this->makeCategory(totalQuantity: 3);

        $category->releaseOneTicket();

        self::assertSame(0, $category->reservedQuantity());
        self::assertSame(3, $category->availableQuantity());
    }

    public function testIsSoldOutReturnsTrueWhenFullyReserved(): void
    {
        $category = $this->makeCategory(totalQuantity: 2);
        $category->reserveOneTicket();
        $category->reserveOneTicket();

        self::assertTrue($category->isSoldOut());
    }

    public function testIsSoldOutReturnsFalseWhenTicketsRemain(): void
    {
        $category = $this->makeCategory(totalQuantity: 2);
        $category->reserveOneTicket();

        self::assertFalse($category->isSoldOut());
    }

    public function testIsSoldOutReturnsFalseOnFreshCategory(): void
    {
        $category = $this->makeCategory(totalQuantity: 10);

        self::assertFalse($category->isSoldOut());
    }

    private function makeCategory(int $totalQuantity = 100): TicketCategory
    {
        return TicketCategory::create(
            id: $this->id,
            name: 'General Admission',
            price: $this->price,
            totalQuantity: $totalQuantity,
        );
    }
}
