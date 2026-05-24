<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\RemoveTicketCategoryCommand;
use App\Domain\Common\Security\Authorization\UserContext;

interface RemoveTicketCategoryUseCase
{
    public function __invoke(RemoveTicketCategoryCommand $command, UserContext $actor): void;
}
