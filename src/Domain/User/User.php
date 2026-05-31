<?php

declare(strict_types=1);

namespace Domain\User;

use Domain\Common\Event\RecordsDomainEvents;
use Domain\Common\Security\Authorization\Permission;
use Domain\User\Event\UserRegistered;
use Domain\User\Exception\InvalidUserName;
use Domain\User\ValueObject\Email;
use Domain\User\ValueObject\UserId;

final class User
{
    use RecordsDomainEvents;

    private function __construct(
        private readonly UserId $id,
        private Email $email,
        private ?string $passwordHash,
        private \DateTimeImmutable $createdAt,
        private Role $role,
        private ?string $firstName = null,
        private ?string $lastName = null,
    ) {
    }

    public static function register(
        UserId $id,
        Email $email,
        ?string $passwordHash,
        \DateTimeImmutable $registeredAt,
        Role $role = Role::BUYER,
        ?string $firstName = null,
        ?string $lastName = null,
    ): self {
        self::assertFirstName($firstName);
        self::assertLastName($lastName);

        $user = new self(
            $id,
            $email,
            $passwordHash,
            $registeredAt,
            $role,
            $firstName,
            $lastName,
        );

        $user->recordEvent(new UserRegistered(
            userId: (string) $id,
            email: (string) $email,
        ));

        return $user;
    }

    public static function reconstitute(
        UserId $id,
        Email $email,
        ?string $passwordHash,
        \DateTimeImmutable $createdAt,
        Role $role,
        ?string $firstName,
        ?string $lastName,
    ): self {
        return new self($id, $email, $passwordHash, $createdAt, $role, $firstName, $lastName);
    }

    public function hasPermission(Permission $permission): bool
    {
        return $this->role()->hasPermission($permission);
    }

    public function promoteToAdmin(): void
    {
        $this->role = Role::ADMIN;
    }

    public function rename(
        ?string $firstName,
        ?string $lastName,
    ): void {
        self::assertFirstName($firstName);
        self::assertLastName($lastName);

        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    private static function assertFirstName(?string $firstName): void
    {
        if ('' === $firstName || is_null($firstName)) {
            throw InvalidUserName::firstName();
        }
    }

    private static function assertLastName(?string $lastName): void
    {
        if ('' === $lastName || is_null($lastName)) {
            throw InvalidUserName::lastName();
        }
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function passwordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function firstName(): ?string
    {
        return $this->firstName;
    }

    public function lastName(): ?string
    {
        return $this->lastName;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
