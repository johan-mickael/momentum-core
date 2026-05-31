<?php

declare(strict_types=1);

namespace Application\Auth\CommandHandler;

use Application\Auth\Command\RegisterUserCommand;
use Application\Auth\Result\RegisterUserResult;

interface RegisterUserUseCase
{
    public function __invoke(RegisterUserCommand $registerUserCommand): RegisterUserResult;
}
