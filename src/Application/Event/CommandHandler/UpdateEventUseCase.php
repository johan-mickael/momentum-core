<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\UpdateEventCommand;
use Application\Event\Result\EventResult;
use Domain\Common\Security\Authorization\UserContext;

interface UpdateEventUseCase
{
    public function __invoke(UpdateEventCommand $command, UserContext $actor): EventResult;
}
