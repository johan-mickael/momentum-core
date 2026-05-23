<?php

declare(strict_types=1);

namespace App\Application\Ticket\CommandHandler;

use App\Application\Ticket\Command\ReserveTicketCommand;
use App\Application\Ticket\Result\ReserveTicketResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface ReserveTicketUseCase
{
    public function __invoke(ReserveTicketCommand $command, UserContext $actor): ReserveTicketResult;
}
