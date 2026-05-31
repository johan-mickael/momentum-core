<?php

declare(strict_types=1);

namespace Domain\User\ValueObject;

use Domain\User\Exception\InvalidPassword;

final class PlainPassword
{
    private const int MIN_LENGTH = 8;

    private string $value;

    public function __construct(string $value)
    {
        if (strlen($value) < self::MIN_LENGTH) {
            throw InvalidPassword::tooShort(self::MIN_LENGTH);
        }

        if (!preg_match('/[A-Z]/', $value)) {
            throw InvalidPassword::missingUppercase();
        }

        if (!preg_match('/[0-9]/', $value)) {
            throw InvalidPassword::missingDigit();
        }

        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    // No __toString() to prevent accidental password exposure in logs
}
