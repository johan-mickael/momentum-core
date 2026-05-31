<?php

declare(strict_types=1);

namespace Domain\User\Exception;

final class UserNotFound extends UserException
{
    public function __construct(string $userId)
    {
        parent::__construct(sprintf('User "%s" not found.', $userId));
    }
}
