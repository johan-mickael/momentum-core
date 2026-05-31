<?php

declare(strict_types=1);

namespace Application\Auth\Result;

final readonly class RegisterUserResult
{
    public function __construct(
        public string $id,
        public string $email,
        public string $role,
        public string $firstName,
        public string $lastName,
    ) {
    }
}
