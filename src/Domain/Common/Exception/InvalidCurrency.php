<?php

declare(strict_types=1);

namespace Domain\Common\Exception;

final class InvalidCurrency extends \DomainException
{
    public static function emptyCode(): self
    {
        return new self('Invalid currency code');
    }

    public static function unsupported(string $code): self
    {
        return new self(sprintf(
            'Currency "%s" is not supported.',
            $code,
        ));
    }
}
