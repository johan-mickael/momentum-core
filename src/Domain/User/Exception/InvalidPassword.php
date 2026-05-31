<?php

declare(strict_types=1);

namespace Domain\User\Exception;

final class InvalidPassword extends UserException
{
    public static function tooShort(int $min): self
    {
        return new self(sprintf('Password must be at least %d characters.', $min));
    }

    public static function missingUppercase(): self
    {
        return new self('Password must contain at least one uppercase letter.');
    }

    public static function missingDigit(): self
    {
        return new self('Password must contain at least one digit.');
    }
}
