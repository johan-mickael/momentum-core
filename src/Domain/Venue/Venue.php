<?php

declare(strict_types=1);

namespace Domain\Venue;

use Domain\Venue\Exception\InvalidVenueName;
use Domain\Venue\ValueObject\Address;
use Domain\Venue\ValueObject\Capacity;
use Domain\Venue\ValueObject\GeoLocation;
use Domain\Venue\ValueObject\VenueId;

final class Venue
{
    private function __construct(
        private readonly VenueId $id,
        private string $name,
        private Address $address,
        private GeoLocation $location,
        private Capacity $capacity,
    ) {
        $this->assertValidName($this->name);
    }

    public static function create(
        VenueId $id,
        string $name,
        Address $address,
        GeoLocation $location,
        Capacity $capacity,
    ): self {
        return new self(
            id: $id,
            name: $name,
            address: $address,
            location: $location,
            capacity: $capacity,
        );
    }

    public function id(): VenueId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function location(): GeoLocation
    {
        return $this->location;
    }

    public function capacity(): Capacity
    {
        return $this->capacity;
    }

    public function changeName(string $name): void
    {
        if ('' === trim($name)) {
            throw new InvalidVenueName('Venue name cannot be empty.');
        }

        $this->name = $name;
    }

    public function changeAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function changeLocation(?GeoLocation $location): void
    {
        $this->location = $location;
    }

    public function changeCapacity(Capacity $capacity): void
    {
        $this->capacity = $capacity;
    }

    private function assertValidName(string $name): void
    {
        if ('' === trim($name)) {
            throw new InvalidVenueName('Venue name cannot be empty.');
        }
    }
}
