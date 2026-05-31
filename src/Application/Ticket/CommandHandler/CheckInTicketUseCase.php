<?php

declare(strict_types=1);

namespace Application\Ticket\CommandHandler;

use Application\Ticket\Command\CheckInTicketCommand;
use Domain\Common\Security\Authorization\UserContext;

interface CheckInTicketUseCase
{
    public function __invoke(CheckInTicketCommand $command, UserContext $actor): void;
}
