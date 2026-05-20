<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Service;

use App\Domain\User\Exception\UserAlreadyExists;
use App\Domain\User\Role;
use App\Domain\User\Service\UserEmailMustUniquenessChecker;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UserEmailMustUniquenessCheckerTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';

    private UserRepositoryInterface&MockObject $repository;
    private UserEmailMustUniquenessChecker $checker;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->checker = new UserEmailMustUniquenessChecker($this->repository);
    }

    public function testAssertPassesWhenEmailIsUnique(): void
    {
        $user = $this->makeUser();

        $this->repository
            ->expects(self::once())
            ->method('findByEmail')
            ->with((string) $user->email())
            ->willReturn(null);

        $this->checker->assert($user);

        // No exception means the check passed.
        $this->addToAssertionCount(1);
    }

    public function testAssertThrowsWhenEmailAlreadyExists(): void
    {
        $user = $this->makeUser();
        $existingUser = $this->makeUser();

        $this->repository
            ->expects(self::once())
            ->method('findByEmail')
            ->willReturn($existingUser);

        $this->expectException(UserAlreadyExists::class);

        $this->checker->assert($user);
    }

    private function makeUser(): User
    {
        return User::register(
            id: UserId::fromString(self::VALID_UUID),
            email: Email::create('john@example.com'),
            passwordHash: 'hashed',
            registeredAt: new DateTimeImmutable('2026-01-01'),
            role: Role::BUYER,
            firstName: 'John',
            lastName: 'Doe',
        );
    }
}
