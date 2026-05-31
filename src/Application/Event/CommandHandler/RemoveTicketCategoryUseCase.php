<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\RemoveTicketCategoryCommand;
use Domain\Common\Security\Authorization\UserContext;

interface RemoveTicketCategoryUseCase
{
    public function __invoke(RemoveTicketCategoryCommand $command, UserContext $actor): void;
}
