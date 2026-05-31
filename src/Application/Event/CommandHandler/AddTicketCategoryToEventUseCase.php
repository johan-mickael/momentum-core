<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\AddTicketCategoryToEventCommand;
use Application\Event\Result\TicketCategoryResult;
use Domain\Common\Security\Authorization\UserContext;

interface AddTicketCategoryToEventUseCase
{
    public function __invoke(AddTicketCategoryToEventCommand $command, UserContext $actor): TicketCategoryResult;
}
