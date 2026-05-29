<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Domain\User\ValueObject\Email;
use App\Domain\User\ValueObject\UnverifiedPlainPassword;

final readonly class LoginCommand
{
    public Email $email;
    public UnverifiedPlainPassword $password;

    public function __construct(
        string $email,
        string $password,
    ) {
        $this->email = Email::create($email);
        $this->password = new UnverifiedPlainPassword($password);
    }
}
