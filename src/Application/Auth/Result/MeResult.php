<?php

declare(strict_types=1);

namespace App\Application\Auth\Result;

final readonly class MeResult
{
    public function __construct(
        public string $id,
        public string $email,
        public string $role,
        public ?string $firstName,
        public ?string $lastName,
    ) {}
}
