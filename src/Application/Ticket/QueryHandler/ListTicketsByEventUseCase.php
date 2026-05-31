<?php

declare(strict_types=1);

namespace Application\Ticket\QueryHandler;

use Application\Common\Result\PaginatedResult;
use Application\Ticket\Query\ListTicketsByEventQuery;
use Domain\Common\Security\Authorization\UserContext;

interface ListTicketsByEventUseCase
{
    public function __invoke(ListTicketsByEventQuery $query, UserContext $actor): PaginatedResult;
}
