<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class InvalidEmail extends UserException
{
    public static function create(string $email): self
    {
        return new self("Invalid email: $email");
    }
}
