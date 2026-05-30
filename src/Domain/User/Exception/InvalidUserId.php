<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class InvalidUserId extends UserException
{
    public static function create(string $userId): self
    {
        return new self("Invalid user id: $userId");
    }
}
