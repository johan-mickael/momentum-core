<?php

declare(strict_types=1);

namespace Application\Ticket\CommandHandler;

use Application\Ticket\Command\ReserveTicketCommand;
use Application\Ticket\Result\ReserveTicketResult;
use Domain\Common\Security\Authorization\UserContext;

interface ReserveTicketUseCase
{
    public function __invoke(ReserveTicketCommand $command, UserContext $actor): ReserveTicketResult;
}
