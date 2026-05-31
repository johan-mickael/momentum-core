<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Venue\Service;

use Domain\Venue\Exception\DuplicateVenueAddress;
use Domain\Venue\Service\VenueAddressUniquenessChecker;
use Domain\Venue\ValueObject\Address;
use Domain\Venue\ValueObject\Capacity;
use Domain\Venue\ValueObject\GeoLocation;
use Domain\Venue\ValueObject\VenueId;
use Domain\Venue\Venue;
use Domain\Venue\VenueRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class VenueAddressUniquenessCheckerTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';

    private VenueRepositoryInterface&MockObject $repository;
    private VenueAddressUniquenessChecker $checker;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VenueRepositoryInterface::class);
        $this->checker = new VenueAddressUniquenessChecker($this->repository);
    }

    public function testCheckPassesWhenAddressIsUnique(): void
    {
        $venue = $this->makeVenue();

        $this->repository
            ->expects(self::once())
            ->method('findByAddress')
            ->with($venue->address())
            ->willReturn(null);

        $this->checker->check($venue);

        $this->addToAssertionCount(1);
    }

    public function testCheckThrowsWhenAddressAlreadyExists(): void
    {
        $venue = $this->makeVenue();
        $existingVenue = $this->makeVenue();

        $this->repository
            ->expects(self::once())
            ->method('findByAddress')
            ->willReturn($existingVenue);

        $this->expectException(DuplicateVenueAddress::class);

        $this->checker->check($venue);
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
