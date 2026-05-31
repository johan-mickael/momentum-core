<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\PublishEventCommand;
use Application\Event\Result\EventResult;
use Domain\Common\Security\Authorization\UserContext;

interface PublishEventUseCase
{
    public function __invoke(PublishEventCommand $command, UserContext $actor): EventResult;
}
