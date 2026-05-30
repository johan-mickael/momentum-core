<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event;

use App\Domain\Common\ValueObject\Currency;
use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\Event;
use App\Domain\Event\EventStatus;
use App\Domain\Event\Exception\EventHasNoTicketCategories;
use App\Domain\Event\Exception\InvalidEventName;
use App\Domain\Event\Exception\InvalidEventStatusTransition;
use App\Domain\Event\ValueObject\DateRange;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\EventSlug;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Venue\ValueObject\VenueId;
use PHPUnit\Framework\TestCase;

final class EventLifecycleTest extends TestCase
{
    private const string EVENT_ID = '123e4567-e89b-12d3-a456-426614174000';
    private const string VENUE_ID = '223e4567-e89b-12d3-a456-426614174001';
    private const string CATEGORY_ID = '323e4567-e89b-12d3-a456-426614174002';
    private const string NEW_VENUE = '423e4567-e89b-12d3-a456-426614174003';

    // ─── publish() ───────────────────────────────────────────────────────────

    public function testPublishDraftEventWithCategoriesSucceeds(): void
    {
        $event = $this->makeDraftEventWithCategory();

        $event->publish();

        self::assertSame(EventStatus::PUBLISHED, $event->status());
    }

    public function testPublishDraftEventWithNoCategoriesThrows(): void
    {
        $event = $this->makeDraftEvent();

        $this->expectException(EventHasNoTicketCategories::class);

        $event->publish();
    }

    public function testPublishAlreadyPublishedEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::PUBLISHED);

        $this->expectException(InvalidEventStatusTransition::class);

        $event->publish();
    }

    public function testPublishCancelledEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::CANCELLED);

        $this->expectException(InvalidEventStatusTransition::class);

        $event->publish();
    }

    public function testPublishEndedEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::ENDED);

        $this->expectException(InvalidEventStatusTransition::class);

        $event->publish();
    }

    // ─── cancel() ────────────────────────────────────────────────────────────

    public function testCancelDraftEventSucceeds(): void
    {
        $event = $this->makeDraftEvent();

        $event->cancel();

        self::assertSame(EventStatus::CANCELLED, $event->status());
    }

    public function testCancelPublishedEventSucceeds(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::PUBLISHED);

        $event->cancel();

        self::assertSame(EventStatus::CANCELLED, $event->status());
    }

    public function testCancelAlreadyCancelledEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::CANCELLED);

        $this->expectException(InvalidEventStatusTransition::class);

        $event->cancel();
    }

    public function testCancelEndedEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::ENDED);

        $this->expectException(InvalidEventStatusTransition::class);

        $event->cancel();
    }

    // ─── end() ───────────────────────────────────────────────────────────────

    public function testEndPublishedEventSucceeds(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::PUBLISHED);

        $event->end();

        self::assertSame(EventStatus::ENDED, $event->status());
    }

    public function testEndDraftEventThrows(): void
    {
        $event = $this->makeDraftEvent();

        $this->expectException(InvalidEventStatusTransition::class);

        $event->end();
    }

    public function testEndCancelledEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::CANCELLED);

        $this->expectException(InvalidEventStatusTransition::class);

        $event->end();
    }

    public function testEndAlreadyEndedEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::ENDED);

        $this->expectException(InvalidEventStatusTransition::class);

        $event->end();
    }

    // ─── reschedule() ────────────────────────────────────────────────────────

    public function testRescheduleDraftEventUpdatesDateRange(): void
    {
        $event = $this->makeDraftEvent();
        $newRange = $this->makeFutureDateRange();

        $event->reschedule($newRange);

        self::assertSame($newRange, $event->dateRange());
    }

    public function testReschedulePublishedEventSucceeds(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::PUBLISHED);
        $newRange = $this->makeFutureDateRange();

        $event->reschedule($newRange);

        self::assertSame($newRange, $event->dateRange());
    }

    public function testRescheduleCancelledEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::CANCELLED);

        $this->expectException(InvalidEventStatusTransition::class);

        $event->reschedule($this->makeFutureDateRange());
    }

    public function testRescheduleEndedEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::ENDED);

        $this->expectException(InvalidEventStatusTransition::class);

        $event->reschedule($this->makeFutureDateRange());
    }

    // ─── updateDetails() ─────────────────────────────────────────────────────

    public function testUpdateDetailsChangesNameDescriptionAndImageUrl(): void
    {
        $event = $this->makeDraftEvent();

        $event->updateDetails('New Name', 'New description', 'https://img.example.com/new.jpg');

        self::assertSame('New Name', $event->name());
        self::assertSame('New description', $event->description());
        self::assertSame('https://img.example.com/new.jpg', $event->imageUrl());
    }

    public function testUpdateDetailsAcceptsNullDescriptionAndImageUrl(): void
    {
        $event = $this->makeDraftEvent();

        $event->updateDetails('New Name', null, null);

        self::assertNull($event->description());
        self::assertNull($event->imageUrl());
    }

    public function testUpdateDetailsWithEmptyNameThrows(): void
    {
        $event = $this->makeDraftEvent();

        $this->expectException(InvalidEventName::class);

        $event->updateDetails('', null, null);
    }

    public function testUpdateDetailsWithWhitespaceOnlyNameThrows(): void
    {
        $event = $this->makeDraftEvent();

        $this->expectException(InvalidEventName::class);

        $event->updateDetails('   ', null, null);
    }

    // ─── relocate() ──────────────────────────────────────────────────────────

    public function testRelocateDraftEventChangesVenue(): void
    {
        $event = $this->makeDraftEvent();
        $newVenueId = VenueId::fromString(self::NEW_VENUE);

        $event->relocate($newVenueId);

        self::assertSame((string) $newVenueId, (string) $event->venueId());
    }

    public function testRelocatePublishedEventSucceeds(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::PUBLISHED);

        $event->relocate(VenueId::fromString(self::NEW_VENUE));

        self::assertSame(self::NEW_VENUE, (string) $event->venueId());
    }

    public function testRelocateCancelledEventThrows(): void
    {
        $event = $this->makeEventWithStatus(EventStatus::CANCELLED);

        $this->expectException(InvalidEventStatusTransition::class);

        $event->relocate(VenueId::fromString(self::NEW_VENUE));
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

    private function makeDraftEventWithCategory(): Event
    {
        $event = $this->makeDraftEvent();

        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_ID),
            name: 'General Admission',
            price: new Money(2500, Currency::fromString('EUR')),
            totalQuantity: 100,
        );

        return $event;
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

    private function makeFutureDateRange(): DateRange
    {
        return DateRange::create(
            new \DateTimeImmutable('2041-01-01 10:00:00'),
            new \DateTimeImmutable('2041-01-01 18:00:00'),
        );
    }
}
