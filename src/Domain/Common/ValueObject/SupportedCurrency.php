<?php

declare(strict_types=1);

namespace App\Domain\Common\ValueObject;

use App\Domain\Common\Exception\UnsupportedCurrency;

enum SupportedCurrency: string
{
    case EUR = 'EUR';
    case AR  = 'AR';

    public static function fromCurrency(Currency $currency): self
    {
        return self::tryFrom($currency->toString())
            ?? throw UnsupportedCurrency::withCode($currency);
    }

    public function toCurrency(): Currency
    {
        return Currency::fromString($this->value);
    }
}
