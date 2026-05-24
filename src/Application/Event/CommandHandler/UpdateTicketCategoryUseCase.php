<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\UpdateTicketCategoryCommand;
use App\Application\Event\Result\TicketCategoryResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface UpdateTicketCategoryUseCase
{
    public function __invoke(UpdateTicketCategoryCommand $command, UserContext $actor): TicketCategoryResult;
}
