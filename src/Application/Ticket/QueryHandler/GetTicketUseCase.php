<?php

declare(strict_types=1);

namespace Application\Ticket\QueryHandler;

use Application\Ticket\Query\GetTicketQuery;
use Application\Ticket\Result\TicketResult;
use Domain\Common\Security\Authorization\UserContext;

interface GetTicketUseCase
{
    public function __invoke(GetTicketQuery $query, UserContext $actor): TicketResult;
}
