<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User;

use Domain\Common\Security\Authorization\Permission;
use Domain\User\Exception\InvalidUserName;
use Domain\User\Role;
use Domain\User\User;
use Domain\User\ValueObject\Email;
use Domain\User\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';

    private UserId $id;
    private Email $email;
    private string $passwordHash;
    private \DateTimeImmutable $registeredAt;

    protected function setUp(): void
    {
        $this->id = UserId::fromString(self::VALID_UUID);
        $this->email = Email::create('john.doe@example.com');
        $this->passwordHash = '$2y$10$hashedpassword';
        $this->registeredAt = new \DateTimeImmutable('2026-01-01 00:00:00');
    }

    public function testRegisterCreatesUserWithCorrectProperties(): void
    {
        $user = User::register(
            id: $this->id,
            email: $this->email,
            passwordHash: $this->passwordHash,
            registeredAt: $this->registeredAt,
            firstName: 'John',
            lastName: 'Doe',
        );

        self::assertSame($this->id, $user->id());
        self::assertSame($this->email, $user->email());
        self::assertSame($this->passwordHash, $user->passwordHash());
        self::assertSame($this->registeredAt, $user->createdAt());
        self::assertSame('John', $user->firstName());
        self::assertSame('Doe', $user->lastName());
    }

    public function testRegisterDefaultRoleIsBuyer(): void
    {
        $user = User::register(
            id: $this->id,
            email: $this->email,
            passwordHash: $this->passwordHash,
            registeredAt: $this->registeredAt,
            firstName: 'John',
            lastName: 'Doe',
        );

        self::assertSame(Role::BUYER, $user->role());
    }

    public function testRegisterWithCustomRole(): void
    {
        $user = User::register(
            id: $this->id,
            email: $this->email,
            passwordHash: $this->passwordHash,
            registeredAt: $this->registeredAt,
            role: Role::ADMIN,
            firstName: 'Jane',
            lastName: 'Admin',
        );

        self::assertSame(Role::ADMIN, $user->role());
    }

    public function testRegisterWithEmptyFirstNameThrows(): void
    {
        $this->expectException(InvalidUserName::class);

        User::register(
            id: $this->id,
            email: $this->email,
            passwordHash: $this->passwordHash,
            registeredAt: $this->registeredAt,
            firstName: '',
            lastName: 'Doe',
        );
    }

    public function testRegisterWithNullFirstNameThrows(): void
    {
        $this->expectException(InvalidUserName::class);

        User::register(
            id: $this->id,
            email: $this->email,
            passwordHash: $this->passwordHash,
            registeredAt: $this->registeredAt,
            firstName: null,
            lastName: 'Doe',
        );
    }

    public function testRegisterWithEmptyLastNameThrows(): void
    {
        $this->expectException(InvalidUserName::class);

        User::register(
            id: $this->id,
            email: $this->email,
            passwordHash: $this->passwordHash,
            registeredAt: $this->registeredAt,
            firstName: 'John',
            lastName: '',
        );
    }

    public function testRegisterWithNullLastNameThrows(): void
    {
        $this->expectException(InvalidUserName::class);

        User::register(
            id: $this->id,
            email: $this->email,
            passwordHash: $this->passwordHash,
            registeredAt: $this->registeredAt,
            firstName: 'John',
            lastName: null,
        );
    }

    public function testPromoteToAdminChangesRoleToAdmin(): void
    {
        $user = $this->makeUser(role: Role::BUYER);

        $user->promoteToAdmin();

        self::assertSame(Role::ADMIN, $user->role());
    }

    public function testPromoteToAdminOnAlreadyAdminUserIsIdempotent(): void
    {
        $user = $this->makeUser(role: Role::ADMIN);

        $user->promoteToAdmin();

        self::assertSame(Role::ADMIN, $user->role());
    }

    public function testRenameUpdatesFirstAndLastName(): void
    {
        $user = $this->makeUser();

        $user->rename('Jane', 'Smith');

        self::assertSame('Jane', $user->firstName());
        self::assertSame('Smith', $user->lastName());
    }

    public function testRenameWithEmptyFirstNameThrows(): void
    {
        $user = $this->makeUser();

        $this->expectException(InvalidUserName::class);

        $user->rename('', 'Doe');
    }

    public function testRenameWithEmptyLastNameThrows(): void
    {
        $user = $this->makeUser();

        $this->expectException(InvalidUserName::class);

        $user->rename('John', '');
    }

    public function testHasPermissionDelegatesToRole(): void
    {
        $adminUser = $this->makeUser(role: Role::ADMIN);
        $buyerUser = $this->makeUser(role: Role::BUYER);

        self::assertTrue($adminUser->hasPermission(Permission::MANAGE_USERS));
        self::assertFalse($buyerUser->hasPermission(Permission::MANAGE_USERS));
    }

    private function makeUser(Role $role = Role::BUYER): User
    {
        return User::register(
            id: $this->id,
            email: $this->email,
            passwordHash: $this->passwordHash,
            registeredAt: $this->registeredAt,
            role: $role,
            firstName: 'John',
            lastName: 'Doe',
        );
    }
}
