<?php

declare(strict_types=1);

namespace App\Domain\Common\Exception;

use App\Domain\Common\ValueObject\Currency;
use DomainException;

final class CurrencyMismatch extends DomainException
{
    public static function between(Currency $first, Currency $second): self
    {
        return new self(sprintf(
            'Cannot operate on mismatched currencies: "%s" and "%s".',
            $first->toString(),
            $second->toString(),
        ));
    }
}
