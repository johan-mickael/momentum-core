<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\AddTicketCategoryToEventCommand;
use App\Application\Event\Result\TicketCategoryResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface AddTicketCategoryToEventUseCase
{
    public function __invoke(AddTicketCategoryToEventCommand $command, UserContext $actor): TicketCategoryResult;
}
