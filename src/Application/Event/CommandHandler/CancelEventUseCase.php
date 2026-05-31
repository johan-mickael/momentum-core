<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\CancelEventCommand;
use Application\Event\Result\EventResult;
use Domain\Common\Security\Authorization\UserContext;

interface CancelEventUseCase
{
    public function __invoke(CancelEventCommand $command, UserContext $actor): EventResult;
}
