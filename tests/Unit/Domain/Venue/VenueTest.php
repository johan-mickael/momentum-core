<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Venue;

use Domain\Venue\Exception\InvalidVenueName;
use Domain\Venue\ValueObject\Address;
use Domain\Venue\ValueObject\Capacity;
use Domain\Venue\ValueObject\GeoLocation;
use Domain\Venue\ValueObject\VenueId;
use Domain\Venue\Venue;
use PHPUnit\Framework\TestCase;

final class VenueTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';
    private const string ANOTHER_UUID = '223e4567-e89b-12d3-a456-426614174001';

    private VenueId $id;
    private Address $address;
    private GeoLocation $location;
    private Capacity $capacity;

    protected function setUp(): void
    {
        $this->id = VenueId::fromString(self::VALID_UUID);
        $this->address = Address::create('1 Rue de la Paix', 'Paris', '75001', 'France');
        $this->location = GeoLocation::create(48.8566, 2.3522);
        $this->capacity = Capacity::fromInteger(500);
    }

    public function testCreateVenueWithValidData(): void
    {
        $venue = Venue::create(
            id: $this->id,
            name: 'Grand Palais',
            address: $this->address,
            location: $this->location,
            capacity: $this->capacity,
        );

        self::assertSame($this->id, $venue->id());
        self::assertSame('Grand Palais', $venue->name());
        self::assertSame($this->address, $venue->address());
        self::assertSame($this->location, $venue->location());
        self::assertSame($this->capacity, $venue->capacity());
    }

    public function testCreateVenueWithEmptyNameThrows(): void
    {
        $this->expectException(InvalidVenueName::class);

        Venue::create(
            id: $this->id,
            name: '',
            address: $this->address,
            location: $this->location,
            capacity: $this->capacity,
        );
    }

    public function testCreateVenueWithWhitespaceOnlyNameThrows(): void
    {
        $this->expectException(InvalidVenueName::class);

        Venue::create(
            id: $this->id,
            name: '   ',
            address: $this->address,
            location: $this->location,
            capacity: $this->capacity,
        );
    }

    public function testChangeNameUpdatesName(): void
    {
        $venue = $this->makeVenue();

        $venue->changeName('Palais Royal');

        self::assertSame('Palais Royal', $venue->name());
    }

    public function testChangeNameWithEmptyStringThrows(): void
    {
        $venue = $this->makeVenue();

        $this->expectException(InvalidVenueName::class);

        $venue->changeName('');
    }

    public function testChangeAddressUpdatesAddress(): void
    {
        $venue = $this->makeVenue();
        $newAddress = Address::create('10 Avenue Montaigne', 'Paris', '75008', 'France');

        $venue->changeAddress($newAddress);

        self::assertSame($newAddress, $venue->address());
    }

    public function testChangeLocationUpdatesLocation(): void
    {
        $venue = $this->makeVenue();
        $newLocation = GeoLocation::create(51.5074, -0.1278);

        $venue->changeLocation($newLocation);

        self::assertSame($newLocation, $venue->location());
    }

    public function testChangeCapacityUpdatesCapacity(): void
    {
        $venue = $this->makeVenue();
        $newCapacity = Capacity::fromInteger(1000);

        $venue->changeCapacity($newCapacity);

        self::assertSame(1000, $venue->capacity()->value);
    }

    private function makeVenue(): Venue
    {
        return Venue::create(
            id: $this->id,
            name: 'Grand Palais',
            address: $this->address,
            location: $this->location,
            capacity: $this->capacity,
        );
    }
}
