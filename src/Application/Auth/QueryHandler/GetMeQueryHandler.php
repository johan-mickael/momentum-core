<?php

declare(strict_types=1);

namespace App\Application\Auth\QueryHandler;

use App\Application\Auth\Query\GetMeQuery;
use App\Application\Auth\Result\MeResult;
use App\Domain\User\Exception\UserNotFound;
use App\Domain\User\UserRepositoryInterface;

final readonly class GetMeQueryHandler implements GetMeUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function __invoke(GetMeQuery $query): MeResult
    {
        $user = $this->users->findById($query->userId);

        if ($user === null) {
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
