<?php

declare(strict_types=1);

namespace App\Application\Ticket\QueryHandler;

use App\Application\Ticket\Query\ListMyTicketsQuery;
use App\Domain\Common\Security\Authorization\UserContext;

interface ListMyTicketsUseCase
{
    /** @return \App\Application\Ticket\Result\TicketResult[] */
    public function __invoke(ListMyTicketsQuery $query, UserContext $actor): array;
}
