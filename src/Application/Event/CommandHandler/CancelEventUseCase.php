<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\CancelEventCommand;
use App\Application\Event\Result\EventResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface CancelEventUseCase
{
    public function __invoke(CancelEventCommand $command, UserContext $actor): EventResult;
}
