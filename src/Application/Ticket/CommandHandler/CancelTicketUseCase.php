<?php

declare(strict_types=1);

namespace App\Application\Ticket\CommandHandler;

use App\Application\Ticket\Command\CancelTicketCommand;
use App\Domain\Common\Security\Authorization\UserContext;

interface CancelTicketUseCase
{
    public function __invoke(CancelTicketCommand $command, UserContext $actor): void;
}
