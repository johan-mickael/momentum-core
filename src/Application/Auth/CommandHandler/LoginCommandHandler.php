<?php

declare(strict_types=1);

namespace App\Application\Auth\CommandHandler;

use App\Application\Auth\Command\LoginCommand;
use App\Domain\Common\Security\PasswordHasherInterface;
use App\Domain\User\Exception\InvalidCredentials;
use App\Domain\User\TokenManagerInterface;
use App\Domain\User\UserRepositoryInterface;

final readonly class LoginCommandHandler implements LoginUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $hasher,
        private TokenManagerInterface $tokenManager,
    ) {
    }

    public function __invoke(LoginCommand $loginCommand): string
    {
        $user = $this->users->findByEmail(
            (string)$loginCommand->email
        );

        if ($user === null) {
            throw InvalidCredentials::create();
        }

        if ($user->passwordHash() === null) {
            throw InvalidCredentials::create();
        }

        if (!$this->hasher->verify($user->passwordHash(), $loginCommand->password->toString())) {
            throw InvalidCredentials::create();
        }

        return $this->tokenManager->generateFor($user);
    }
}
