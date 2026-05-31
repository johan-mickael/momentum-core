<?php

declare(strict_types=1);

namespace Application\Auth\CommandHandler;

use Application\Auth\Command\LoginWithGoogleCommand;
use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Common\Id\IdGeneratorInterface;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\User\TokenManagerInterface;
use Domain\User\User;
use Domain\User\UserRepositoryInterface;
use Domain\User\ValueObject\Email;
use Domain\User\ValueObject\UserId;
use Psr\Clock\ClockInterface;

final readonly class LoginWithGoogleCommandHandler implements LoginWithGoogleUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private TokenManagerInterface $tokenManager,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transaction,
        private ClockInterface $clock,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(LoginWithGoogleCommand $command): string
    {
        $user = $this->users->findByEmail($command->email);

        if (null === $user) {
            $user = User::register(
                id: UserId::fromString($this->idGenerator->generate()),
                email: Email::create($command->email),
                passwordHash: null,
                registeredAt: $this->clock->now(),
                firstName: $command->firstName ?? 'Unknown',
                lastName: $command->lastName ?? 'User',
            );

            $this->users->save($user);
            $this->dispatcher->dispatch(...$user->pullRecordedEvents());
            $this->transaction->flush();
        }

        return $this->tokenManager->generateFor($user);
    }
}
