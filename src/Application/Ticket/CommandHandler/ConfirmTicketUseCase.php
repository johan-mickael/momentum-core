<?php

declare(strict_types=1);

namespace App\Application\Ticket\CommandHandler;

use App\Application\Ticket\Command\ConfirmTicketCommand;
use App\Domain\Common\Security\Authorization\UserContext;

interface ConfirmTicketUseCase
{
    public function __invoke(ConfirmTicketCommand $command, UserContext $actor): void;
}
