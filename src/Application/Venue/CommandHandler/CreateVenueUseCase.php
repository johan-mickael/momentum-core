<?php

declare(strict_types=1);

namespace Application\Venue\CommandHandler;

use Application\Venue\Command\CreateVenueCommand;
use Application\Venue\Result\CreateVenueResult;
use Domain\Common\Security\Authorization\UserContext;

interface CreateVenueUseCase
{
    public function __invoke(
        CreateVenueCommand $command,
        UserContext $actor,
    ): CreateVenueResult;
}
