<?php

declare(strict_types=1);

namespace Domain\Common\Exception;

use Domain\Common\ValueObject\Currency;

final class CurrencyMismatch extends \DomainException
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
