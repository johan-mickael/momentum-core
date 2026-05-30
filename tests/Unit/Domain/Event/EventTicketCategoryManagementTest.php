<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event;

use App\Domain\Common\ValueObject\Currency;
use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\Event;
use App\Domain\Event\EventStatus;
use App\Domain\Event\Exception\CannotAddCategoryToEvent;
use App\Domain\Event\Exception\CannotRemoveTicketCategoryWithReservations;
use App\Domain\Event\Exception\DuplicateTicketCategoryNameForEvent;
use App\Domain\Event\Exception\TicketCategoryCapacityInvalid;
use App\Domain\Event\Exception\TicketCategoryNotFound;
use App\Domain\Event\Exception\TicketsCannotBeReserved;
use App\Domain\Event\TicketCategory;
use App\Domain\Event\ValueObject\DateRange;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\EventSlug;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Venue\ValueObject\VenueId;
use PHPUnit\Framework\TestCase;

final class EventTicketCategoryManagementTest extends TestCase
{
    private const string EVENT_ID = '123e4567-e89b-12d3-a456-426614174000';
    private const string VENUE_ID = '223e4567-e89b-12d3-a456-426614174001';
    private const string CATEGORY_A_ID = '323e4567-e89b-12d3-a456-426614174002';
    private const string CATEGORY_B_ID = '423e4567-e89b-12d3-a456-426614174003';
    private const string UNKNOWN_ID = '999e4567-e89b-12d3-a456-426614174099';

    private Money $price;

    protected function setUp(): void
    {
        $this->price = new Money(2500, Currency::fromString('EUR'));
    }

    // ─── addTicketCategory() ─────────────────────────────────────────────────

    public function testAddCategoryToDraftEventSucceeds(): void
    {
        $event = $this->makeDraftEvent();

        $category = $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'VIP',
            price: $this->price,
            totalQuantity: 50,
        );

        self::assertCount(1, $event->ticketCategories());
        self::assertSame('VIP', $category->name());
        self::assertSame(50, $category->totalQuantity());
    }

    public function testAddCategoryToPublishedEventSucceeds(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::PUBLISHED);

        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'VIP',
            price: $this->price,
            totalQuantity: 50,
        );

        self::assertCount(1, $event->ticketCategories());
    }

    public function testAddCategoryToCancelledEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::CANCELLED);

        $this->expectException(CannotAddCategoryToEvent::class);

        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'VIP',
            price: $this->price,
            totalQuantity: 50,
        );
    }

    public function testAddCategoryToEndedEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::ENDED);

        $this->expectException(CannotAddCategoryToEvent::class);

        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'VIP',
            price: $this->price,
            totalQuantity: 50,
        );
    }

    public function testAddCategoryWithDuplicateNameThrows(): void
    {
        $event = $this->makeDraftEvent();
        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'VIP',
            price: $this->price,
            totalQuantity: 50,
        );

        $this->expectException(DuplicateTicketCategoryNameForEvent::class);

        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_B_ID),
            name: 'VIP',
            price: $this->price,
            totalQuantity: 30,
        );
    }

    // ─── updateTicketCategory() ───────────────────────────────────────────────

    public function testUpdateTicketCategoryChangesNamePriceAndQuantity(): void
    {
        $event = $this->makeEventWithOneCategory();
        $newPrice = new Money(5000, Currency::fromString('EUR'));

        $category = $event->updateTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'Premium',
            price: $newPrice,
            totalQuantity: 200,
        );

        self::assertSame('Premium', $category->name());
        self::assertTrue($category->price()->equals($newPrice));
        self::assertSame(200, $category->totalQuantity());
    }

    public function testUpdateTicketCategoryWithSameNameSucceeds(): void
    {
        $event = $this->makeEventWithOneCategory();

        $category = $event->updateTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'General Admission',
            price: new Money(3000, Currency::fromString('EUR')),
            totalQuantity: 150,
        );

        self::assertSame('General Admission', $category->name());
        self::assertSame(3000, $category->price()->amount());
    }

    public function testUpdateTicketCategoryWithNameUsedByAnotherCategoryThrows(): void
    {
        $event = $this->makeDraftEvent();
        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'General Admission',
            price: $this->price,
            totalQuantity: 100,
        );
        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_B_ID),
            name: 'VIP',
            price: $this->price,
            totalQuantity: 20,
        );

        $this->expectException(DuplicateTicketCategoryNameForEvent::class);

        $event->updateTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_B_ID),
            name: 'General Admission',
            price: $this->price,
            totalQuantity: 20,
        );
    }

    public function testUpdateTicketCategoryWithCapacityBelowCurrentReservationsThrows(): void
    {
        $reservedCategory = TicketCategory::reconstitute(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'General Admission',
            price: $this->price,
            totalQuantity: 100,
            reservedQuantity: 50,
        );
        $event = $this->makeEventWithCategories([$reservedCategory]);

        $this->expectException(TicketCategoryCapacityInvalid::class);

        $event->updateTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'General Admission',
            price: $this->price,
            totalQuantity: 30,
        );
    }

    public function testUpdateTicketCategoryWithExactCapacityMatchingReservationsSucceeds(): void
    {
        $reservedCategory = TicketCategory::reconstitute(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'General Admission',
            price: $this->price,
            totalQuantity: 100,
            reservedQuantity: 50,
        );
        $event = $this->makeEventWithCategories([$reservedCategory]);

        $category = $event->updateTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'General Admission',
            price: $this->price,
            totalQuantity: 50,
        );

        self::assertSame(50, $category->totalQuantity());
        self::assertSame(0, $category->availableQuantity());
    }

    public function testUpdateNonExistentTicketCategoryThrows(): void
    {
        $event = $this->makeEventWithOneCategory();

        $this->expectException(TicketCategoryNotFound::class);

        $event->updateTicketCategory(
            id: TicketCategoryId::fromString(self::UNKNOWN_ID),
            name: 'Ghost',
            price: $this->price,
            totalQuantity: 10,
        );
    }

    // ─── removeTicketCategory() ───────────────────────────────────────────────

    public function testRemoveTicketCategorySucceeds(): void
    {
        $event = $this->makeEventWithOneCategory();

        $event->removeTicketCategory(TicketCategoryId::fromString(self::CATEGORY_A_ID));

        self::assertCount(0, $event->ticketCategories());
    }

    public function testRemoveOneOfSeveralCategoriesLeavesOthersIntact(): void
    {
        $event = $this->makeDraftEvent();
        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'General Admission',
            price: $this->price,
            totalQuantity: 100,
        );
        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_B_ID),
            name: 'VIP',
            price: $this->price,
            totalQuantity: 20,
        );

        $event->removeTicketCategory(TicketCategoryId::fromString(self::CATEGORY_A_ID));

        $remaining = $event->ticketCategories();
        self::assertCount(1, $remaining);
        self::assertSame('VIP', $remaining[0]->name());
    }

    public function testRemoveTicketCategoryWithReservationsThrows(): void
    {
        $reservedCategory = TicketCategory::reconstitute(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'General Admission',
            price: $this->price,
            totalQuantity: 100,
            reservedQuantity: 1,
        );
        $event = $this->makeEventWithCategories([$reservedCategory]);

        $this->expectException(CannotRemoveTicketCategoryWithReservations::class);

        $event->removeTicketCategory(TicketCategoryId::fromString(self::CATEGORY_A_ID));
    }

    public function testRemoveNonExistentTicketCategoryThrows(): void
    {
        $event = $this->makeEventWithOneCategory();

        $this->expectException(TicketCategoryNotFound::class);

        $event->removeTicketCategory(TicketCategoryId::fromString(self::UNKNOWN_ID));
    }

    // ─── reserveTicketInCategory() / releaseTicketInCategory() ───────────────

    public function testReserveTicketInCategoryDecrementsAvailability(): void
    {
        $event = $this->makePublishedEventWithOneCategory();

        $event->reserveTicketInCategory(TicketCategoryId::fromString(self::CATEGORY_A_ID));

        self::assertSame(99, $event->categoryById(TicketCategoryId::fromString(self::CATEGORY_A_ID))->availableQuantity());
    }

    public function testReserveTicketInCategoryThrowsWhenEventNotPublished(): void
    {
        $event = $this->makeEventWithOneCategory();

        $this->expectException(TicketsCannotBeReserved::class);

        $event->reserveTicketInCategory(TicketCategoryId::fromString(self::CATEGORY_A_ID));
    }

    public function testReleaseTicketInCategoryIncrementsAvailability(): void
    {
        $category = TicketCategory::reconstitute(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'General Admission',
            price: $this->price,
            totalQuantity: 100,
            reservedQuantity: 5,
        );
        $event = $this->makeEventWithCategories([$category]);

        $event->releaseTicketInCategory(TicketCategoryId::fromString(self::CATEGORY_A_ID));

        self::assertSame(96, $event->categoryById(TicketCategoryId::fromString(self::CATEGORY_A_ID))->availableQuantity());
    }

    // ─── categoryById() ──────────────────────────────────────────────────────

    public function testCategoryByIdReturnsCorrectCategory(): void
    {
        $event = $this->makeEventWithOneCategory();

        $category = $event->categoryById(TicketCategoryId::fromString(self::CATEGORY_A_ID));

        self::assertSame('General Admission', $category->name());
    }

    public function testCategoryByIdWithUnknownIdThrows(): void
    {
        $event = $this->makeEventWithOneCategory();

        $this->expectException(TicketCategoryNotFound::class);

        $event->categoryById(TicketCategoryId::fromString(self::UNKNOWN_ID));
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function makeDraftEvent(): Event
    {
        return Event::create(
            id: EventId::fromString(self::EVENT_ID),
            name: 'Summer Festival',
            venueId: VenueId::fromString(self::VENUE_ID),
            slug: EventSlug::create('summer-festival'),
            dateRange: DateRange::create(
                new \DateTimeImmutable('2040-07-01 18:00:00'),
                new \DateTimeImmutable('2040-07-01 23:00:00'),
            ),
            createdAt: new \DateTimeImmutable('2026-01-01'),
        );
    }

    private function makeEventWithStatus(EventStatus $status): Event
    {
        return Event::reconstitute(
            id: EventId::fromString(self::EVENT_ID),
            name: 'Summer Festival',
            venueId: VenueId::fromString(self::VENUE_ID),
            slug: EventSlug::create('summer-festival'),
            dateRange: DateRange::create(
                new \DateTimeImmutable('2040-07-01 18:00:00'),
                new \DateTimeImmutable('2040-07-01 23:00:00'),
            ),
            status: $status,
            createdAt: new \DateTimeImmutable('2026-01-01'),
        );
    }

    private function makeEventWithOneCategory(): Event
    {
        $event = $this->makeDraftEvent();

        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
            name: 'General Admission',
            price: $this->price,
            totalQuantity: 100,
        );

        return $event;
    }

    private function makePublishedEventWithOneCategory(): Event
    {
        return Event::reconstitute(
            id: EventId::fromString(self::EVENT_ID),
            name: 'Summer Festival',
            venueId: VenueId::fromString(self::VENUE_ID),
            slug: EventSlug::create('summer-festival'),
            dateRange: DateRange::create(
                new \DateTimeImmutable('2040-07-01 18:00:00'),
                new \DateTimeImmutable('2040-07-01 23:00:00'),
            ),
            status: EventStatus::PUBLISHED,
            createdAt: new \DateTimeImmutable('2026-01-01'),
            ticketCategories: [
                TicketCategory::reconstitute(
                    id: TicketCategoryId::fromString(self::CATEGORY_A_ID),
                    name: 'General Admission',
                    price: $this->price,
                    totalQuantity: 100,
                    reservedQuantity: 0,
                ),
            ],
        );
    }

    /** @param TicketCategory[] $categories */
    private function makeEventWithCategories(array $categories): Event
    {
        return Event::reconstitute(
            id: EventId::fromString(self::EVENT_ID),
            name: 'Summer Festival',
            venueId: VenueId::fromString(self::VENUE_ID),
            slug: EventSlug::create('summer-festival'),
            dateRange: DateRange::create(
                new \DateTimeImmutable('2040-07-01 18:00:00'),
                new \DateTimeImmutable('2040-07-01 23:00:00'),
            ),
            status: EventStatus::DRAFT,
            createdAt: new \DateTimeImmutable('2026-01-01'),
            ticketCategories: $categories,
        );
    }
}
