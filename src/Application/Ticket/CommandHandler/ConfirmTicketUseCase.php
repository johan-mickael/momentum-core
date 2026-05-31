<?php

declare(strict_types=1);

namespace Application\Ticket\CommandHandler;

use Application\Ticket\Command\ConfirmTicketCommand;
use Domain\Common\Security\Authorization\UserContext;

interface ConfirmTicketUseCase
{
    public function __invoke(ConfirmTicketCommand $command, UserContext $actor): void;
}
