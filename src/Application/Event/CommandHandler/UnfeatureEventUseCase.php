<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\UnfeatureEventCommand;
use Application\Event\Result\EventResult;
use Domain\Common\Security\Authorization\UserContext;

interface UnfeatureEventUseCase
{
    public function __invoke(UnfeatureEventCommand $command, UserContext $actor): EventResult;
}
