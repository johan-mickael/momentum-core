<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Venue;

use App\Domain\Venue\Exception\InvalidGeoLocation;
use App\Domain\Venue\ValueObject\GeoLocation;
use PHPUnit\Framework\TestCase;

final class GeoLocationTest extends TestCase
{
    public function testCreateWithValidCoordinates(): void
    {
        $location = GeoLocation::create(48.8566, 2.3522);

        self::assertSame(48.8566, $location->latitude());
        self::assertSame(2.3522, $location->longitude());
    }

    public function testCreateWithBoundaryLatitudeValues(): void
    {
        $min = GeoLocation::create(-90.0, 0.0);
        $max = GeoLocation::create(90.0, 0.0);

        self::assertSame(-90.0, $min->latitude());
        self::assertSame(90.0, $max->latitude());
    }

    public function testCreateWithBoundaryLongitudeValues(): void
    {
        $min = GeoLocation::create(0.0, -180.0);
        $max = GeoLocation::create(0.0, 180.0);

        self::assertSame(-180.0, $min->longitude());
        self::assertSame(180.0, $max->longitude());
    }

    public function testCreateWithLatitudeTooLowThrows(): void
    {
        $this->expectException(InvalidGeoLocation::class);

        GeoLocation::create(-90.1, 0.0);
    }

    public function testCreateWithLatitudeTooHighThrows(): void
    {
        $this->expectException(InvalidGeoLocation::class);

        GeoLocation::create(90.1, 0.0);
    }

    public function testCreateWithLongitudeTooLowThrows(): void
    {
        $this->expectException(InvalidGeoLocation::class);

        GeoLocation::create(0.0, -180.1);
    }

    public function testCreateWithLongitudeTooHighThrows(): void
    {
        $this->expectException(InvalidGeoLocation::class);

        GeoLocation::create(0.0, 180.1);
    }

    public function testEqualsReturnsTrueForSameCoordinates(): void
    {
        $a = GeoLocation::create(48.8566, 2.3522);
        $b = GeoLocation::create(48.8566, 2.3522);

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentLatitude(): void
    {
        $a = GeoLocation::create(48.8566, 2.3522);
        $b = GeoLocation::create(51.5074, 2.3522);

        self::assertFalse($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentLongitude(): void
    {
        $a = GeoLocation::create(48.8566, 2.3522);
        $b = GeoLocation::create(48.8566, -0.1278);

        self::assertFalse($a->equals($b));
    }

    public function testToStringContainsCoordinates(): void
    {
        $location = GeoLocation::create(48.8566, 2.3522);
        $string = (string) $location;

        self::assertStringContainsString('48', $string);
        self::assertStringContainsString('2', $string);
    }
}
