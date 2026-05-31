<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event;

use Domain\Common\ValueObject\Currency;
use Domain\Common\ValueObject\Money;
use Domain\Event\Exception\CannotReserveTicketInCategory;
use Domain\Event\Exception\TicketCategoryCapacityInvalid;
use Domain\Event\Exception\TicketCategoryNameEmpty;
use Domain\Event\TicketCategory;
use Domain\Event\ValueObject\TicketCategoryId;
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

    // ─── update() ────────────────────────────────────────────────────────────

    public function testUpdateChangesNamePriceAndQuantity(): void
    {
        $category = $this->makeCategory(totalQuantity: 100);
        $newPrice = new Money(5000, Currency::fromString('EUR'));

        $category->update('Premium', $newPrice, 200);

        self::assertSame('Premium', $category->name());
        self::assertTrue($category->price()->equals($newPrice));
        self::assertSame(200, $category->totalQuantity());
    }

    public function testUpdatePreservesReservedQuantity(): void
    {
        $category = $this->makeCategory(totalQuantity: 100);
        $category->reserveOneTicket();
        $category->reserveOneTicket();

        $category->update('Premium', $this->price, 150);

        self::assertSame(2, $category->reservedQuantity());
        self::assertSame(148, $category->availableQuantity());
    }

    public function testUpdateWithEmptyNameThrows(): void
    {
        $category = $this->makeCategory();

        $this->expectException(TicketCategoryNameEmpty::class);

        $category->update('', $this->price, 100);
    }

    public function testUpdateWithWhitespaceOnlyNameThrows(): void
    {
        $category = $this->makeCategory();

        $this->expectException(TicketCategoryNameEmpty::class);

        $category->update('   ', $this->price, 100);
    }

    public function testUpdateWithZeroCapacityThrows(): void
    {
        $category = $this->makeCategory();

        $this->expectException(TicketCategoryCapacityInvalid::class);

        $category->update('VIP', $this->price, 0);
    }

    public function testUpdateWithCapacityBelowCurrentReservationsThrows(): void
    {
        $category = TicketCategory::reconstitute(
            id: $this->id,
            name: 'VIP',
            price: $this->price,
            totalQuantity: 100,
            reservedQuantity: 60,
        );

        $this->expectException(TicketCategoryCapacityInvalid::class);

        $category->update('VIP', $this->price, 50);
    }

    public function testUpdateWithCapacityExactlyMatchingReservationsSucceeds(): void
    {
        $category = TicketCategory::reconstitute(
            id: $this->id,
            name: 'VIP',
            price: $this->price,
            totalQuantity: 100,
            reservedQuantity: 50,
        );

        $category->update('VIP', $this->price, 50);

        self::assertSame(50, $category->totalQuantity());
        self::assertSame(0, $category->availableQuantity());
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
