<?php

declare(strict_types=1);

namespace Application\Ticket\QueryHandler;

use Application\Common\Result\PaginatedResult;
use Application\Ticket\Query\ListMyTicketsQuery;
use Domain\Common\Security\Authorization\UserContext;

interface ListMyTicketsUseCase
{
    public function __invoke(ListMyTicketsQuery $query, UserContext $actor): PaginatedResult;
}
