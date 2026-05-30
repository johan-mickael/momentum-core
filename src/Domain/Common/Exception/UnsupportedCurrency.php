<?php

declare(strict_types=1);

namespace App\Domain\Common\Exception;

use App\Domain\Common\ValueObject\Currency;

final class UnsupportedCurrency extends \DomainException
{
    public static function withCode(Currency $currency): self
    {
        return new self(sprintf(
            'Currency "%s" is not supported by this platform.',
            $currency->toString(),
        ));
    }
}
