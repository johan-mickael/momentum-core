<?php

declare(strict_types=1);

namespace Tests\Fixture\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SymfonyUserProxy implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private string $email)
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
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
        return null;
    }
}
