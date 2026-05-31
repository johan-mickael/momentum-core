<?php

declare(strict_types=1);

namespace Infrastructure\Symfony\Security;

use Domain\Common\Security\Authorization\UserContext;
use Domain\User\User as DomainUser;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SymfonySecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private DomainUser $user
    ) {
    }

    public function getDomainUser(): DomainUser
    {
        return $this->user;
    }

    public function getUserContext(): UserContext
    {
        return new UserContext(
            id: $this->user->id(),
            role: $this->user->role(),
        );
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->user->email();
    }

    public function getRoles(): array
    {
        return $this->user->role()->securityRoles();
    }

    public function eraseCredentials(): void
    {
    }

    public function getPassword(): ?string
    {
        return $this->user->passwordHash();
    }

    public function id(): string
    {
        return (string) $this->user->id();
    }
}
