<?php

declare(strict_types=1);

namespace App\Application\Ticket\CommandHandler;

use App\Application\Ticket\Command\MarkTicketAsUsedCommand;
use App\Domain\Common\Security\Authorization\UserContext;

interface MarkTicketAsUsedUseCase
{
    public function __invoke(MarkTicketAsUsedCommand $command, UserContext $actor): void;
}
