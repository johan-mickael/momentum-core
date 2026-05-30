<?php

declare(strict_types=1);

namespace App\Application\Auth\CommandHandler;

use App\Application\Auth\Command\LoginWithGoogleCommand;

interface LoginWithGoogleUseCase
{
    public function __invoke(LoginWithGoogleCommand $command): string;
}
