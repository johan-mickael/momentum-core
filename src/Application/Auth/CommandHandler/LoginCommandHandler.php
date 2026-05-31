<?php

declare(strict_types=1);

namespace Application\Auth\CommandHandler;

use Application\Auth\Command\LoginCommand;
use Domain\Common\Security\PasswordHasherInterface;
use Domain\User\Exception\InvalidCredentials;
use Domain\User\TokenManagerInterface;
use Domain\User\UserRepositoryInterface;

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
            (string) $loginCommand->email
        );

        if (null === $user) {
            throw InvalidCredentials::create();
        }

        if (null === $user->passwordHash()) {
            throw InvalidCredentials::create();
        }

        if (!$this->hasher->verify($user->passwordHash(), $loginCommand->password->toString())) {
            throw InvalidCredentials::create();
        }

        return $this->tokenManager->generateFor($user);
    }
}
