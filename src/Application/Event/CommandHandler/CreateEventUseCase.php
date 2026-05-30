<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\CreateEventCommand;
use App\Application\Event\Result\CreateEventResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface CreateEventUseCase
{
    public function __invoke(CreateEventCommand $command, UserContext $actor): CreateEventResult;
}
