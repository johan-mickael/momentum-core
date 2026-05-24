<?php

declare(strict_types=1);

namespace App\Application\Auth\CommandHandler;

use App\Application\Auth\Command\RegisterUserCommand;
use App\Application\Auth\Result\RegisterUserResult;
use App\Domain\Common\Id\IdGeneratorInterface;
use App\Domain\Common\Security\PasswordHasherInterface;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\User\Service\UserEmailUniquenessChecker;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\User\ValueObject\UserId;
use Psr\Clock\ClockInterface;

final readonly class RegisterUserCommandHandler implements RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserEmailUniquenessChecker $userEmailMustBeUnique,
        private PasswordHasherInterface $passwordHasher,
        private TransactionManagerInterface $transaction,
        private ClockInterface $clock,
        private IdGeneratorInterface $idGenerator,
    ) {}

    public function __invoke(RegisterUserCommand $registerUserCommand): RegisterUserResult
    {
        $passwordHash = $this->passwordHasher->hash($registerUserCommand->password->toString());

        $user = User::register(
            id: UserId::fromString($this->idGenerator->generate()),
            email: $registerUserCommand->email,
            passwordHash: $passwordHash,
            registeredAt: $this->clock->now(),
            firstName: $registerUserCommand->firstName,
            lastName: $registerUserCommand->lastName,
        );

        $this->userEmailMustBeUnique->assert($user);

        $this->users->save($user);
        $this->transaction->flush();

        return new RegisterUserResult(
            (string) $user->id(),
            (string) $user->email(),
            $user->role()->value,
            $user->firstName(),
            $user->lastName(),
        );
    }
}
