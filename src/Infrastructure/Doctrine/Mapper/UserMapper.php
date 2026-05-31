<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Mapper;

use Domain\User\User;
use Domain\User\ValueObject\Email;
use Domain\User\ValueObject\UserId;
use Infrastructure\Doctrine\Entity\UserEntity;

final class UserMapper
{
    public static function toDomain(UserEntity $userEntity): User
    {
        return User::reconstitute(
            id: UserId::fromString($userEntity->id),
            email: Email::create($userEntity->email),
            passwordHash: $userEntity->passwordHash,
            createdAt: $userEntity->createdAt,
            role: $userEntity->role,
            firstName: $userEntity->firstName,
            lastName: $userEntity->lastName,
        );
    }

    public static function toEntity(User $user): UserEntity
    {
        $userEntity = new UserEntity();

        $userEntity->id = (string) $user->id();
        $userEntity->email = (string) $user->email();
        $userEntity->passwordHash = $user->passwordHash();
        $userEntity->role = $user->role();
        $userEntity->firstName = $user->firstName();
        $userEntity->lastName = $user->lastName();
        $userEntity->createdAt = $user->createdAt();

        return $userEntity;
    }
}
