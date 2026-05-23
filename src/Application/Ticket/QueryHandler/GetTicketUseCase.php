<?php

declare(strict_types=1);

namespace App\Application\Ticket\QueryHandler;

use App\Application\Ticket\Query\GetTicketQuery;
use App\Application\Ticket\Result\TicketResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface GetTicketUseCase
{
    public function __invoke(GetTicketQuery $query, UserContext $actor): TicketResult;
}
