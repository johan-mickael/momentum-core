<?php

declare(strict_types=1);

namespace App\Application\Ticket\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Ticket\Query\ListTicketsByEventQuery;
use App\Domain\Common\Security\Authorization\UserContext;

interface ListTicketsByEventUseCase
{
    public function __invoke(ListTicketsByEventQuery $query, UserContext $actor): PaginatedResult;
}
