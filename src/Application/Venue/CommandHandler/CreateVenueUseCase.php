<?php

declare(strict_types=1);

namespace App\Application\Venue\CommandHandler;

use App\Application\Venue\Command\CreateVenueCommand;
use App\Application\Venue\Result\CreateVenueResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface CreateVenueUseCase
{
    public function __invoke(
        CreateVenueCommand $command,
        UserContext $actor,
    ): CreateVenueResult;
}
