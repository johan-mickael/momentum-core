<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common\ValueObject;

use Domain\Common\Exception\InvalidCurrency;
use Domain\Common\ValueObject\Currency;
use PHPUnit\Framework\TestCase;

final class CurrencyTest extends TestCase
{
    public function testFromStringWithValidCode(): void
    {
        $currency = Currency::fromString('EUR');

        self::assertSame('EUR', $currency->toString());
    }

    public function testFromStringNormalizesToUppercase(): void
    {
        $currency = Currency::fromString('eur');

        self::assertSame('EUR', $currency->toString());
    }

    public function testFromStringTrimsWhitespace(): void
    {
        $currency = Currency::fromString('  EUR  ');

        self::assertSame('EUR', $currency->toString());
    }

    public function testFromStringWithEmptyCodeThrows(): void
    {
        $this->expectException(InvalidCurrency::class);

        Currency::fromString('');
    }

    public function testFromStringWithWhitespaceOnlyThrows(): void
    {
        $this->expectException(InvalidCurrency::class);

        Currency::fromString('   ');
    }

    public function testEqualsReturnsTrueForSameCode(): void
    {
        $a = Currency::fromString('EUR');
        $b = Currency::fromString('eur');

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentCode(): void
    {
        $a = Currency::fromString('EUR');
        $b = Currency::fromString('USD');

        self::assertFalse($a->equals($b));
    }

    public function testToStringReturnsNormalizedCode(): void
    {
        $currency = Currency::fromString('gbp');

        self::assertSame('GBP', $currency->toString());
    }
}
