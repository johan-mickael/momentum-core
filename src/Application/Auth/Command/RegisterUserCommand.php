<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Domain\User\ValueObject\Email;
use App\Domain\User\ValueObject\PlainPassword;

final readonly class RegisterUserCommand
{
    public Email $email;
    public PlainPassword $password;

    public function __construct(
        string $email,
        string $password,
        public ?string $firstName = null,
        public ?string $lastName = null,
    ) {
        $this->email = Email::create($email);
        $this->password = new PlainPassword($password);
    }
}
