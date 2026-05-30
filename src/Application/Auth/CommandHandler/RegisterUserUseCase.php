<?php

declare(strict_types=1);

namespace App\Application\Auth\CommandHandler;

use App\Application\Auth\Command\RegisterUserCommand;
use App\Application\Auth\Result\RegisterUserResult;

interface RegisterUserUseCase
{
    public function __invoke(RegisterUserCommand $registerUserCommand): RegisterUserResult;
}
