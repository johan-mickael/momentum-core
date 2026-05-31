<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Venue;

use Domain\Venue\Exception\InvalidAddress;
use Domain\Venue\ValueObject\Address;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    public function testCreateWithValidData(): void
    {
        $address = Address::create('123 Main St', 'Paris', '75001', 'France');

        self::assertSame('123 Main St', $address->street());
        self::assertSame('Paris', $address->city());
        self::assertSame('75001', $address->zipCode());
        self::assertSame('France', $address->country());
    }

    public function testCreateWithEmptyStreetThrows(): void
    {
        $this->expectException(InvalidAddress::class);

        Address::create('', 'Paris', '75001', 'France');
    }

    public function testCreateWithWhitespaceOnlyStreetThrows(): void
    {
        $this->expectException(InvalidAddress::class);

        Address::create('   ', 'Paris', '75001', 'France');
    }

    public function testCreateWithEmptyCityThrows(): void
    {
        $this->expectException(InvalidAddress::class);

        Address::create('123 Main St', '', '75001', 'France');
    }

    public function testCreateWithEmptyZipCodeThrows(): void
    {
        $this->expectException(InvalidAddress::class);

        Address::create('123 Main St', 'Paris', '', 'France');
    }

    public function testCreateWithEmptyCountryThrows(): void
    {
        $this->expectException(InvalidAddress::class);

        Address::create('123 Main St', 'Paris', '75001', '');
    }

    public function testEqualsReturnsTrueForIdenticalAddresses(): void
    {
        $a = Address::create('123 Main St', 'Paris', '75001', 'France');
        $b = Address::create('123 Main St', 'Paris', '75001', 'France');

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseWhenStreetDiffers(): void
    {
        $a = Address::create('123 Main St', 'Paris', '75001', 'France');
        $b = Address::create('456 Other Ave', 'Paris', '75001', 'France');

        self::assertFalse($a->equals($b));
    }

    public function testEqualsReturnsFalseWhenCityDiffers(): void
    {
        $a = Address::create('123 Main St', 'Paris', '75001', 'France');
        $b = Address::create('123 Main St', 'Lyon', '75001', 'France');

        self::assertFalse($a->equals($b));
    }

    public function testToStringFormatsCorrectly(): void
    {
        $address = Address::create('123 Main St', 'Paris', '75001', 'France');

        self::assertSame('123 Main St, 75001 Paris, France', (string) $address);
    }
}
