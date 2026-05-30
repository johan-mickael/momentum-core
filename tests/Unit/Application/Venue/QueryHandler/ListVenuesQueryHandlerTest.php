<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Venue\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Venue\Query\ListVenuesQuery;
use App\Application\Venue\QueryHandler\ListVenuesQueryHandler;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\ValueObject\PaginatedList;
use App\Domain\User\Role;
use App\Domain\User\ValueObject\UserId;
use App\Domain\Venue\Exception\ListVenuesForbidden;
use App\Domain\Venue\ValueObject\Address;
use App\Domain\Venue\ValueObject\Capacity;
use App\Domain\Venue\ValueObject\GeoLocation;
use App\Domain\Venue\ValueObject\VenueId;
use App\Domain\Venue\Venue;
use App\Domain\Venue\VenueRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ListVenuesQueryHandlerTest extends TestCase
{
    private const string VENUE_ID = '123e4567-e89b-12d3-a456-426614174000';
    private const string USER_ID = '223e4567-e89b-12d3-a456-426614174001';

    private VenueRepositoryInterface&MockObject $repository;
    private AuthorizationServiceInterface&MockObject $auth;
    private ListVenuesQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VenueRepositoryInterface::class);
        $this->auth = $this->createMock(AuthorizationServiceInterface::class);
        $this->handler = new ListVenuesQueryHandler($this->repository, $this->auth);

        $this->repository->method('getById')->willReturn($this->makeVenue());
    }

    public function testUnauthorizedActorThrowsListVenuesForbidden(): void
    {
        $this->auth->method('authorize')->willReturn(false);
        $this->repository->expects(self::never())->method('listVenues')
            ->willThrowException(new \LogicException('listVenues() must not be called'));

        $this->expectException(ListVenuesForbidden::class);

        ($this->handler)($this->makeQuery(), $this->makeActor(Role::BUYER));
    }

    public function testAuthorizedActorReceivesPaginatedResult(): void
    {
        $this->auth->method('authorize')->willReturn(true);

        $this->repository->expects(self::once())->method('listVenues')
            ->willReturn(new PaginatedList(items: [$this->makeVenue()], total: 1, page: 1, limit: 20));

        $result = ($this->handler)($this->makeQuery(), $this->makeActor(Role::STAFF));

        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertCount(1, $result->data);
        self::assertSame(1, $result->pagination->total);
    }

    public function testFiltersPassedToRepository(): void
    {
        $this->auth->method('authorize')->willReturn(true);

        $this->repository->expects(self::once())->method('listVenues')
            ->with(self::anything(), self::anything(), 'Paris', 'France')
            ->willReturn(new PaginatedList([], 0, 1, 20));

        ($this->handler)(
            new ListVenuesQuery(page: 1, limit: 20, sort: 'name', direction: 'ASC', cityFilter: 'Paris', countryFilter: 'France'),
            $this->makeActor(Role::STAFF),
        );
    }

    public function testAuthorizationUsesListAction(): void
    {
        $actor = $this->makeActor(Role::BUYER);

        $this->auth->expects(self::once())->method('authorize')
            ->with($actor, Action::LIST, Venue::class)
            ->willReturn(false);

        $this->repository->expects(self::never())->method('listVenues')
            ->willThrowException(new \LogicException('listVenues() must not be called'));

        $this->expectException(ListVenuesForbidden::class);

        ($this->handler)($this->makeQuery(), $actor);
    }

    public function testPaginationMetaReflectsRepositoryData(): void
    {
        $this->auth->method('authorize')->willReturn(true);

        $this->repository->expects(self::once())->method('listVenues')
            ->willReturn(new PaginatedList(items: [], total: 55, page: 3, limit: 10));

        $result = ($this->handler)(
            new ListVenuesQuery(page: 3, limit: 10, sort: 'name', direction: 'ASC'),
            $this->makeActor(Role::STAFF),
        );

        self::assertSame(55, $result->pagination->total);
        self::assertSame(3, $result->pagination->page);
        self::assertSame(10, $result->pagination->limit);
        self::assertSame(6, $result->pagination->totalPages);
    }

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function makeActor(Role $role): UserContext
    {
        return new UserContext(UserId::fromString(self::USER_ID), $role);
    }

    private function makeQuery(): ListVenuesQuery
    {
        return new ListVenuesQuery(page: 1, limit: 20, sort: 'name', direction: 'ASC');
    }

    private function makeVenue(): Venue
    {
        return Venue::create(
            id: VenueId::fromString(self::VENUE_ID),
            name: 'Grand Palais',
            address: Address::create('1 Rue de la Paix', 'Paris', '75001', 'France'),
            location: GeoLocation::create(48.8566, 2.3522),
            capacity: Capacity::fromInteger(500),
        );
    }
}
