<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class UserAlreadyExists extends \DomainException
{
    public static function create(string $email): self
    {
        return new self("User with email: '{$email}' already exists");
    }
}
