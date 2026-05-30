<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Common\ValueObject;

use App\Domain\Common\Exception\UnsupportedCurrency;
use App\Domain\Common\ValueObject\Currency;
use App\Domain\Common\ValueObject\SupportedCurrency;
use PHPUnit\Framework\TestCase;

final class SupportedCurrencyTest extends TestCase
{
    public function testFromCurrencyWithSupportedEur(): void
    {
        $currency = Currency::fromString('EUR');

        $supported = SupportedCurrency::fromCurrency($currency);

        self::assertSame(SupportedCurrency::EUR, $supported);
    }

    public function testFromCurrencyWithUnsupportedCodeThrows(): void
    {
        $currency = Currency::fromString('USD');

        $this->expectException(UnsupportedCurrency::class);

        SupportedCurrency::fromCurrency($currency);
    }

    public function testToCurrencyReturnsMatchingCurrency(): void
    {
        $currency = SupportedCurrency::EUR->toCurrency();

        self::assertSame('EUR', $currency->toString());
    }

    public function testRoundTripFromCurrencyAndBack(): void
    {
        $original = Currency::fromString('EUR');
        $supported = SupportedCurrency::fromCurrency($original);
        $restored = $supported->toCurrency();

        self::assertTrue($original->equals($restored));
    }
}
