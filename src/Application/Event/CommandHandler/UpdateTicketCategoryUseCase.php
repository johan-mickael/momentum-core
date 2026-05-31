<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\UpdateTicketCategoryCommand;
use Application\Event\Result\TicketCategoryResult;
use Domain\Common\Security\Authorization\UserContext;

interface UpdateTicketCategoryUseCase
{
    public function __invoke(UpdateTicketCategoryCommand $command, UserContext $actor): TicketCategoryResult;
}
