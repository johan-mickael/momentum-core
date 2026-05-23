<?php

declare(strict_types=1);

namespace App\Application\Ticket\QueryHandler;

use App\Application\Ticket\Query\ListTicketsByEventQuery;
use App\Domain\Common\Security\Authorization\UserContext;

interface ListTicketsByEventUseCase
{
    /** @return \App\Application\Ticket\Result\TicketResult[] */
    public function __invoke(ListTicketsByEventQuery $query, UserContext $actor): array;
}
