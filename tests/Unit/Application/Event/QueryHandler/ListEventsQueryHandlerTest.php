<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Event\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Event\Query\ListEventsQuery;
use App\Application\Event\QueryHandler\ListEventsQueryHandler;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\ValueObject\PaginatedList;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Common\ValueObject\SortDirection;
use App\Domain\Event\Event;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\EventStatus;
use App\Domain\Event\Exception\ListEventsForbidden;
use App\Domain\Event\ValueObject\DateRange;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\EventSlug;
use App\Domain\Event\ValueObject\EventVisibility;
use App\Domain\User\Role;
use App\Domain\User\ValueObject\UserId;
use App\Domain\Venue\ValueObject\Address;
use App\Domain\Venue\ValueObject\Capacity;
use App\Domain\Venue\ValueObject\GeoLocation;
use App\Domain\Venue\ValueObject\VenueId;
use App\Domain\Venue\Venue;
use App\Domain\Venue\VenueRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ListEventsQueryHandlerTest extends TestCase
{
    private const string EVENT_ID = '123e4567-e89b-12d3-a456-426614174000';
    private const string VENUE_ID = '223e4567-e89b-12d3-a456-426614174001';
    private const string USER_ID  = '323e4567-e89b-12d3-a456-426614174002';

    private EventRepositoryInterface&MockObject     $events;
    private VenueRepositoryInterface&MockObject     $venues;
    private AuthorizationServiceInterface&MockObject $auth;
    private ListEventsQueryHandler $handler;

    protected function setUp(): void
    {
        $this->events  = $this->createMock(EventRepositoryInterface::class);
        $this->venues  = $this->createMock(VenueRepositoryInterface::class);
        $this->auth    = $this->createMock(AuthorizationServiceInterface::class);
        $this->handler = new ListEventsQueryHandler($this->events, $this->venues, $this->auth);

        // Suppress PHPUnit 13 return-value-generation notices: configure ALL
        // non-nullable interface methods. Individual tests override with expects().
        $this->events->method('getById')->willReturn($this->makeEvent());
        $this->venues->method('getById')->willReturn($this->makeVenue());
        $this->venues->method('listVenues')->willReturn(new PaginatedList([], 0, 1, 20));
    }

    public function testUnauthorizedActorThrowsListEventsForbidden(): void
    {
        $this->auth->method('authorize')->willReturn(false);
        $this->events->expects(self::never())->method('list')
            ->willThrowException(new \LogicException('list() must not be called'));

        $this->expectException(ListEventsForbidden::class);

        ($this->handler)($this->makeQuery(), $this->makeActor(Role::BUYER));
    }

    public function testAuthorizedActorReceivesPaginatedResult(): void
    {
        $event = $this->makeEvent();
        $venue = $this->makeVenue();

        $this->auth->method('authorize')->willReturn(true);

        $this->events->expects(self::once())->method('list')
            ->willReturn(new PaginatedList(items: [$event], total: 1, page: 1, limit: 20));

        $this->venues->expects(self::once())->method('getById')
            ->with($event->venueId())
            ->willReturn($venue);

        $result = ($this->handler)($this->makeQuery(), $this->makeActor(Role::STAFF));

        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertCount(1, $result->data);
        self::assertSame(1, $result->pagination->total);
        self::assertSame(1, $result->pagination->page);
        self::assertSame(20, $result->pagination->limit);
        self::assertSame(1, $result->pagination->totalPages);
    }

    public function testEmptyResultReturnsPaginatedResultWithNoData(): void
    {
        $this->auth->method('authorize')->willReturn(true);

        $this->events->expects(self::once())->method('list')
            ->willReturn(new PaginatedList(items: [], total: 0, page: 1, limit: 20));

        $this->venues->expects(self::never())->method('getById')
            ->willThrowException(new \LogicException('getById() must not be called'));

        $result = ($this->handler)($this->makeQuery(), $this->makeActor(Role::STAFF));

        self::assertCount(0, $result->data);
        self::assertSame(0, $result->pagination->total);
        self::assertSame(0, $result->pagination->totalPages);
    }

    public function testRepositoryCalledWithCorrectVisibilityForStaff(): void
    {
        $this->auth->method('authorize')->willReturn(true);

        $this->events->expects(self::once())->method('list')
            ->with(
                EventVisibility::ALL,
                self::isInstanceOf(PaginationCriteria::class),
                self::isInstanceOf(SortCriteria::class),
                null,
            )
            ->willReturn(new PaginatedList([], 0, 1, 20));

        ($this->handler)($this->makeQuery(), $this->makeActor(Role::STAFF));
    }

    public function testStatusFilterPassedToRepository(): void
    {
        $this->auth->method('authorize')->willReturn(true);

        $this->events->expects(self::once())->method('list')
            ->with(
                self::anything(),
                self::anything(),
                self::anything(),
                EventStatus::PUBLISHED,
            )
            ->willReturn(new PaginatedList([], 0, 1, 20));

        ($this->handler)(
            new ListEventsQuery(
                pagination:   PaginationCriteria::default(),
                sort:         new SortCriteria('startAt', SortDirection::ASC),
                statusFilter: 'PUBLISHED',
            ),
            $this->makeActor(Role::STAFF),
        );
    }

    public function testAuthorizationCheckUsesListAction(): void
    {
        $this->auth->expects(self::once())->method('authorize')
            ->with($this->makeActor(Role::BUYER), Action::LIST, Event::class)
            ->willReturn(false);

        $this->events->expects(self::never())->method('list')
            ->willThrowException(new \LogicException('list() must not be called'));

        $this->expectException(ListEventsForbidden::class);

        ($this->handler)($this->makeQuery(), $this->makeActor(Role::BUYER));
    }

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function makeActor(Role $role): UserContext
    {
        return new UserContext(UserId::fromString(self::USER_ID), $role);
    }

    private function makeQuery(): ListEventsQuery
    {
        return new ListEventsQuery(
            pagination: PaginationCriteria::default(),
            sort:       new SortCriteria('startAt', SortDirection::ASC),
        );
    }

    private function makeEvent(): Event
    {
        return Event::create(
            id:        EventId::fromString(self::EVENT_ID),
            name:      'Test Event',
            venueId:   VenueId::fromString(self::VENUE_ID),
            slug:      EventSlug::create('test-event'),
            dateRange: DateRange::create(
                new DateTimeImmutable('2026-06-01 19:00'),
                new DateTimeImmutable('2026-06-01 22:00'),
            ),
            createdAt: new DateTimeImmutable('2026-01-01'),
        );
    }

    private function makeVenue(): Venue
    {
        return Venue::create(
            id:       VenueId::fromString(self::VENUE_ID),
            name:     'Test Venue',
            address:  Address::create('1 Rue Test', 'Paris', '75001', 'France'),
            location: GeoLocation::create(48.8566, 2.3522),
            capacity: Capacity::fromInteger(500),
        );
    }
}
