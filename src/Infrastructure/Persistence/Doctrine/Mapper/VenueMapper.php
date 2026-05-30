<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Venue\ValueObject\Address;
use App\Domain\Venue\ValueObject\Capacity;
use App\Domain\Venue\ValueObject\GeoLocation;
use App\Domain\Venue\ValueObject\VenueId;
use App\Domain\Venue\Venue;
use App\Infrastructure\Persistence\Doctrine\Entity\VenueEntity;

final class VenueMapper
{
    public static function toDomain(VenueEntity $venueEntity): Venue
    {
        return Venue::create(
            VenueId::fromString($venueEntity->id),
            $venueEntity->name,
            Address::create(
                $venueEntity->street,
                $venueEntity->city,
                $venueEntity->zipCode,
                $venueEntity->country,
            ),
            GeoLocation::create(
                $venueEntity->latitude,
                $venueEntity->longitude,
            ),
            Capacity::fromInteger($venueEntity->capacity),
        );
    }

    public static function toEntity(Venue $venue): VenueEntity
    {
        $venueEntity = new VenueEntity();

        $venueEntity->id = (string) $venue->id();
        $venueEntity->name = $venue->name();

        $venueAddress = $venue->address();

        $venueEntity->street = $venueAddress->street();
        $venueEntity->city = $venueAddress->city();
        $venueEntity->zipCode = $venueAddress->zipCode();
        $venueEntity->country = $venueAddress->country();

        $venueGeoLocation = $venue->location();
        $venueEntity->latitude = $venueGeoLocation->latitude();
        $venueEntity->longitude = $venueGeoLocation->longitude();

        $venueEntity->capacity = $venue->capacity()->value;

        return $venueEntity;
    }
}
