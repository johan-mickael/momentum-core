<?php

declare(strict_types=1);

namespace Application\Auth\CommandHandler;

use Application\Auth\Command\LoginWithGoogleCommand;

interface LoginWithGoogleUseCase
{
    public function __invoke(LoginWithGoogleCommand $command): string;
}
