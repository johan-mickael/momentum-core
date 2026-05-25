<?php

declare(strict_types=1);

namespace App\Application\Ticket\CommandHandler;

use App\Application\Ticket\Command\CheckInTicketCommand;
use App\Domain\Common\Security\Authorization\UserContext;

interface CheckInTicketUseCase
{
    public function __invoke(CheckInTicketCommand $command, UserContext $actor): void;
}
