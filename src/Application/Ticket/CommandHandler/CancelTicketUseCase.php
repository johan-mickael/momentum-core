<?php

declare(strict_types=1);

namespace Application\Ticket\CommandHandler;

use Application\Ticket\Command\CancelTicketCommand;
use Domain\Common\Security\Authorization\UserContext;

interface CancelTicketUseCase
{
    public function __invoke(CancelTicketCommand $command, UserContext $actor): void;
}
