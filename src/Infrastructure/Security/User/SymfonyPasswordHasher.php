<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\User;

use App\Domain\Common\Security\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {
    }

    public function hash(string $plainPassword): string
    {
        return $this->hasher->hashPassword(
            new SymfonySystemUser(),
            $plainPassword
        );
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        $user = new SymfonySystemUser($hashedPassword);

        return $this->hasher->isPasswordValid(
            $user,
            $plainPassword
        );
    }
}
