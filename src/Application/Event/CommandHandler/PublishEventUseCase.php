<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\PublishEventCommand;
use App\Application\Event\Result\EventResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface PublishEventUseCase
{
    public function __invoke(PublishEventCommand $command, UserContext $actor): EventResult;
}
