<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\UpdateEventCommand;
use App\Application\Event\Result\EventResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface UpdateEventUseCase
{
    public function __invoke(UpdateEventCommand $command, UserContext $actor): EventResult;
}
