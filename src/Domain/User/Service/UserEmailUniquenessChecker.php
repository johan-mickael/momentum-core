<?php

declare(strict_types=1);

namespace Domain\User\Service;

use Domain\User\Exception\UserAlreadyExists;
use Domain\User\User;
use Domain\User\UserRepositoryInterface;

final readonly class UserEmailUniquenessChecker
{
    public function __construct(private UserRepositoryInterface $userRepository)
    {
    }

    public function assert(User $user): void
    {
        if ($this->userRepository->findByEmail((string) $user->email())) {
            throw new UserAlreadyExists("User with email '{$user->email()}' already exists");
        }
    }
}
