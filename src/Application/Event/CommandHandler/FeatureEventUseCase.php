<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\FeatureEventCommand;
use Application\Event\Result\EventResult;
use Domain\Common\Security\Authorization\UserContext;

interface FeatureEventUseCase
{
    public function __invoke(FeatureEventCommand $command, UserContext $actor): EventResult;
}
