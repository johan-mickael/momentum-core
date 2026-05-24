<?php

namespace App\Domain\User\Service;

use App\Domain\User\Exception\UserAlreadyExists;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;

final readonly class UserEmailMustUniquenessChecker
{
    public function __construct(private UserRepositoryInterface $userRepository)
    {
    }

    public function assert(User $user): void
    {
        if ($this->userRepository->findByEmail((string)$user->email())) {
            throw new UserAlreadyExists("User with email '{$user->email()}' already exists");
        }
    }
}
