<?php

declare(strict_types=1);

namespace Application\Auth\QueryHandler;

use Application\Auth\Query\GetMeQuery;
use Application\Auth\Result\MeResult;
use Domain\User\Exception\UserNotFound;
use Domain\User\UserRepositoryInterface;

final readonly class GetMeQueryHandler implements GetMeUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function __invoke(GetMeQuery $query): MeResult
    {
        $user = $this->users->findById($query->userId);

        if (null === $user) {
            throw new UserNotFound($query->userId);
        }

        return new MeResult(
            id: (string) $user->id(),
            email: (string) $user->email(),
            role: $user->role()->value,
            firstName: $user->firstName(),
            lastName: $user->lastName(),
        );
    }
}
