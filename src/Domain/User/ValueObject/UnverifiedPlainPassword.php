<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

final class UnverifiedPlainPassword
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
