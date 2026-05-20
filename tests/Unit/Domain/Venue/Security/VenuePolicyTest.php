<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Venue\Security;

use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\User\Role;
use App\Domain\User\ValueObject\UserId;
use App\Domain\Venue\Security\Authorization\VenueContext;
use App\Domain\Venue\Security\Authorization\VenuePolicy;
use App\Domain\Venue\ValueObject\Address;
use App\Domain\Venue\ValueObject\Capacity;
use App\Domain\Venue\ValueObject\GeoLocation;
use App\Domain\Venue\ValueObject\VenueId;
use App\Domain\Venue\Venue;
use PHPUnit\Framework\TestCase;

final class VenuePolicyTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';

    private VenuePolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new VenuePolicy();
    }

    public function testSupportsReturnsTrueForVenueClass(): void
    {
        self::assertTrue($this->policy->supports(Venue::class));
    }

    public function testSupportsReturnsTrueForVenueContext(): void
    {
        $context = new VenueContext($this->makeVenue());

        self::assertTrue($this->policy->supports($context));
    }

    public function testSupportsReturnsFalseForOtherClass(): void
    {
        self::assertFalse($this->policy->supports('App\Domain\Event\Event'));
    }

    public function testCreateAllowsStaff(): void
    {
        $actor = $this->makeUserContext(Role::STAFF);

        self::assertTrue($this->policy->authorize($actor, Action::CREATE, Venue::class));
    }

    public function testCreateAllowsAdmin(): void
    {
        $actor = $this->makeUserContext(Role::ADMIN);

        self::assertTrue($this->policy->authorize($actor, Action::CREATE, Venue::class));
    }

    public function testCreateDeniesRegularUser(): void
    {
        $actor = $this->makeUserContext(Role::USER);

        self::assertFalse($this->policy->authorize($actor, Action::CREATE, Venue::class));
    }

    public function testCreateDeniesBuyer(): void
    {
        $actor = $this->makeUserContext(Role::BUYER);

        self::assertFalse($this->policy->authorize($actor, Action::CREATE, Venue::class));
    }

    public function testListAllowsStaff(): void
    {
        $actor = $this->makeUserContext(Role::STAFF);

        self::assertTrue($this->policy->authorize($actor, Action::LIST, Venue::class));
    }

    public function testListAllowsAdmin(): void
    {
        $actor = $this->makeUserContext(Role::ADMIN);

        self::assertTrue($this->policy->authorize($actor, Action::LIST, Venue::class));
    }

    public function testListDeniesRegularUser(): void
    {
        $actor = $this->makeUserContext(Role::USER);

        self::assertFalse($this->policy->authorize($actor, Action::LIST, Venue::class));
    }

    public function testUnmatchedActionReturnsFalse(): void
    {
        $actor = $this->makeUserContext(Role::ADMIN);

        self::assertFalse($this->policy->authorize($actor, Action::DELETE, Venue::class));
    }

    private function makeUserContext(Role $role): UserContext
    {
        return new UserContext(
            id: UserId::fromString(self::VALID_UUID),
            role: $role,
        );
    }

    private function makeVenue(): Venue
    {
        return Venue::create(
            id: VenueId::fromString(self::VALID_UUID),
            name: 'Grand Palais',
            address: Address::create('1 Rue de la Paix', 'Paris', '75001', 'France'),
            location: GeoLocation::create(48.8566, 2.3522),
            capacity: Capacity::fromInteger(500),
        );
    }
}
