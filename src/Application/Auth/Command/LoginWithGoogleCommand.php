<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class LoginWithGoogleCommand
{
    public function __construct(
        public string $email,
        public ?string $firstName,
        public ?string $lastName,
    ) {
    }
}
