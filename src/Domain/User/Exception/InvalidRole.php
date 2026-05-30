<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class InvalidRole extends UserException
{
    public static function fromValue(string $value): self
    {
        return new self(sprintf('Invalid role "%s".', $value));
    }
}
