<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Venue\Service;

use App\Domain\Venue\Exception\DuplicateVenueName;
use App\Domain\Venue\Service\VenueNameUniquenessChecker;
use App\Domain\Venue\ValueObject\Address;
use App\Domain\Venue\ValueObject\Capacity;
use App\Domain\Venue\ValueObject\GeoLocation;
use App\Domain\Venue\ValueObject\VenueId;
use App\Domain\Venue\Venue;
use App\Domain\Venue\VenueRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class VenueNameUniquenessCheckerTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';

    private VenueRepositoryInterface&MockObject $repository;
    private VenueNameUniquenessChecker $checker;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VenueRepositoryInterface::class);
        $this->checker = new VenueNameUniquenessChecker($this->repository);
    }

    public function testCheckPassesWhenNameIsUnique(): void
    {
        $venue = $this->makeVenue();

        $this->repository
            ->expects(self::once())
            ->method('findByName')
            ->with($venue->name())
            ->willReturn(null);

        $this->checker->check($venue);

        $this->addToAssertionCount(1);
    }

    public function testCheckThrowsWhenNameAlreadyExists(): void
    {
        $venue = $this->makeVenue();
        $existingVenue = $this->makeVenue();

        $this->repository
            ->expects(self::once())
            ->method('findByName')
            ->willReturn($existingVenue);

        $this->expectException(DuplicateVenueName::class);

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
