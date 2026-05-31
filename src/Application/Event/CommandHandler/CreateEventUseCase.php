<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\CreateEventCommand;
use Application\Event\Result\CreateEventResult;
use Domain\Common\Security\Authorization\UserContext;

interface CreateEventUseCase
{
    public function __invoke(CreateEventCommand $command, UserContext $actor): CreateEventResult;
}
