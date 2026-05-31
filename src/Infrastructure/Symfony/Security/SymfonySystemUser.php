<?php

declare(strict_types=1);

namespace Infrastructure\Symfony\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SymfonySystemUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private ?string $hashedPassword = null
    ) {
    }

    public function setPassword(string $hashed): void
    {
        $this->hashedPassword = $hashed;
    }

    public function getUserIdentifier(): string
    {
        return 'system';
    }

    public function getRoles(): array
    {
        return [];
    }

    public function eraseCredentials(): void
    {
    }

    public function getPassword(): ?string
    {
        return $this->hashedPassword;
    }
}
