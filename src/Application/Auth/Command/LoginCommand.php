<?php

declare(strict_types=1);

namespace Application\Auth\Command;

use Domain\User\ValueObject\Email;
use Domain\User\ValueObject\UnverifiedPlainPassword;

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
