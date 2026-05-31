<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common\ValueObject;

use Domain\Common\Exception\CurrencyMismatch;
use Domain\Common\Exception\NegativeMoneyAmount;
use Domain\Common\ValueObject\Currency;
use Domain\Common\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    private Currency $eur;
    private Currency $usd;

    protected function setUp(): void
    {
        $this->eur = Currency::fromString('EUR');
        $this->usd = Currency::fromString('USD');
    }

    public function testConstructWithValidPositiveAmount(): void
    {
        $money = new Money(1000, $this->eur);

        self::assertSame(1000, $money->amount());
        self::assertTrue($money->currency()->equals($this->eur));
    }

    public function testConstructWithZeroAmountIsValid(): void
    {
        $money = new Money(0, $this->eur);

        self::assertSame(0, $money->amount());
    }

    public function testConstructWithNegativeAmountThrows(): void
    {
        $this->expectException(NegativeMoneyAmount::class);

        new Money(-1, $this->eur);
    }

    public function testAddSameCurrencyReturnsCorrectSum(): void
    {
        $a = new Money(500, $this->eur);
        $b = new Money(300, $this->eur);

        $result = $a->add($b);

        self::assertSame(800, $result->amount());
        self::assertTrue($result->currency()->equals($this->eur));
    }

    public function testAddPreservesImmutability(): void
    {
        $a = new Money(500, $this->eur);
        $b = new Money(300, $this->eur);

        $result = $a->add($b);

        self::assertSame(500, $a->amount());
        self::assertSame(300, $b->amount());
        self::assertSame(800, $result->amount());
    }

    public function testAddDifferentCurrenciesThrows(): void
    {
        $eur = new Money(500, $this->eur);
        $usd = new Money(300, $this->usd);

        $this->expectException(CurrencyMismatch::class);

        $eur->add($usd);
    }

    public function testEqualsReturnsTrueForSameAmountAndCurrency(): void
    {
        $a = new Money(500, $this->eur);
        $b = new Money(500, $this->eur);

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentAmount(): void
    {
        $a = new Money(500, $this->eur);
        $b = new Money(600, $this->eur);

        self::assertFalse($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentCurrency(): void
    {
        $a = new Money(500, $this->eur);
        $b = new Money(500, $this->usd);

        self::assertFalse($a->equals($b));
    }
}
