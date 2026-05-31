<?php

declare(strict_types=1);

namespace Application\Ticket\CommandHandler;

use Application\Ticket\Command\MarkTicketAsUsedCommand;
use Domain\Common\Security\Authorization\UserContext;

interface MarkTicketAsUsedUseCase
{
    public function __invoke(MarkTicketAsUsedCommand $command, UserContext $actor): void;
}
