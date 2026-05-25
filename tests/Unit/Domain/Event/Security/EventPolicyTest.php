<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event\Security;

use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Event\Event;
use App\Domain\Event\Security\Authorization\EventContext;
use App\Domain\Event\Security\Authorization\EventPolicy;
use App\Domain\Event\EventStatus;
use App\Domain\Event\ValueObject\DateRange;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\EventSlug;
use App\Domain\User\Role;
use App\Domain\User\ValueObject\UserId;
use App\Domain\Venue\ValueObject\VenueId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EventPolicyTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';
    private const string ANOTHER_UUID = '223e4567-e89b-12d3-a456-426614174001';

    private EventPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new EventPolicy();
    }

    public function testSupportsReturnsTrueForEventClass(): void
    {
        self::assertTrue($this->policy->supports(Event::class));
    }

    public function testSupportsReturnsTrueForEventContext(): void
    {
        $context = new EventContext($this->makeEvent());

        self::assertTrue($this->policy->supports($context));
    }

    public function testSupportsReturnsFalseForOtherClass(): void
    {
        self::assertFalse($this->policy->supports('App\Domain\Venue\Venue'));
    }

    public function testCreateAllowsStaff(): void
    {
        $actor = $this->makeUserContext(Role::STAFF);

        self::assertTrue($this->policy->authorize($actor, Action::CREATE, Event::class));
    }

    public function testCreateAllowsAdmin(): void
    {
        $actor = $this->makeUserContext(Role::ADMIN);

        self::assertTrue($this->policy->authorize($actor, Action::CREATE, Event::class));
    }

    public function testCreateDeniesRegularUser(): void
    {
        $actor = $this->makeUserContext(Role::USER);

        self::assertFalse($this->policy->authorize($actor, Action::CREATE, Event::class));
    }

    public function testCreateDeniesBuyer(): void
    {
        $actor = $this->makeUserContext(Role::BUYER);

        self::assertFalse($this->policy->authorize($actor, Action::CREATE, Event::class));
    }

    public function testListAllowsAnyRole(): void
    {
        foreach ([Role::ADMIN, Role::STAFF, Role::BUYER, Role::USER] as $role) {
            $actor = $this->makeUserContext($role);

            self::assertTrue(
                $this->policy->authorize($actor, Action::LIST, Event::class),
                "Expected LIST to be allowed for role {$role->value}",
            );
        }
    }

    public function testViewPublishedEventAllowsAnyRole(): void
    {
        $context = new EventContext($this->makeEvent(EventStatus::PUBLISHED));

        foreach ([Role::ADMIN, Role::STAFF, Role::BUYER, Role::USER] as $role) {
            $actor = $this->makeUserContext($role);

            self::assertTrue(
                $this->policy->authorize($actor, Action::VIEW, $context),
                "Expected VIEW of PUBLISHED event to be allowed for role {$role->value}",
            );
        }
    }

    public function testViewDraftEventAllowsStaffOnly(): void
    {
        $context = new EventContext($this->makeEvent());

        foreach ([Role::ADMIN, Role::STAFF] as $role) {
            $actor = $this->makeUserContext($role);
            self::assertTrue(
                $this->policy->authorize($actor, Action::VIEW, $context),
                "Expected VIEW of DRAFT event to be allowed for role {$role->value}",
            );
        }

        foreach ([Role::BUYER, Role::USER] as $role) {
            $actor = $this->makeUserContext($role);
            self::assertFalse(
                $this->policy->authorize($actor, Action::VIEW, $context),
                "Expected VIEW of DRAFT event to be denied for role {$role->value}",
            );
        }
    }

    public function testUnmatchedActionReturnsFalse(): void
    {
        $actor = $this->makeUserContext(Role::ADMIN);

        self::assertFalse($this->policy->authorize($actor, Action::DELETE, Event::class));
    }

    private function makeUserContext(Role $role): UserContext
    {
        return new UserContext(
            id: UserId::fromString(self::VALID_UUID),
            role: $role,
        );
    }

    private function makeEvent(EventStatus $status = EventStatus::DRAFT): Event
    {
        return Event::reconstitute(
            id: EventId::fromString(self::VALID_UUID),
            name: 'Test Event',
            venueId: VenueId::fromString(self::ANOTHER_UUID),
            slug: EventSlug::create('test-event'),
            dateRange: DateRange::create(
                new DateTimeImmutable('2040-01-01 10:00:00'),
                new DateTimeImmutable('2040-01-01 12:00:00'),
            ),
            status: $status,
            createdAt: new DateTimeImmutable('2026-01-01'),
        );
    }
}
