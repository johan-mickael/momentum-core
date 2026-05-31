<?php

declare(strict_types=1);

namespace Application\Auth\CommandHandler;

use Application\Auth\Command\RegisterUserCommand;
use Application\Auth\Result\RegisterUserResult;
use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Common\Id\IdGeneratorInterface;
use Domain\Common\Security\PasswordHasherInterface;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\User\Service\UserEmailUniquenessChecker;
use Domain\User\User;
use Domain\User\UserRepositoryInterface;
use Domain\User\ValueObject\UserId;
use Psr\Clock\ClockInterface;

final readonly class RegisterUserCommandHandler implements RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserEmailUniquenessChecker $userEmailMustBeUnique,
        private PasswordHasherInterface $passwordHasher,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transaction,
        private ClockInterface $clock,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

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
        $this->dispatcher->dispatch(...$user->pullRecordedEvents());
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
