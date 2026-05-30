<?php

declare(strict_types=1);

namespace App\Application\Auth\CommandHandler;

use App\Application\Auth\Command\LoginWithGoogleCommand;
use App\Application\Common\Event\DomainEventDispatcherInterface;
use App\Domain\Common\Id\IdGeneratorInterface;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\User\TokenManagerInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use App\Domain\User\ValueObject\UserId;
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
