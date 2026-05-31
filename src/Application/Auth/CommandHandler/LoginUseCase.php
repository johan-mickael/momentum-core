<?php

declare(strict_types=1);

namespace Application\Auth\CommandHandler;

use Application\Auth\Command\LoginCommand;

interface LoginUseCase
{
    public function __invoke(LoginCommand $loginCommand): string;
}
