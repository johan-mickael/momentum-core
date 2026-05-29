<?php

declare(strict_types=1);

namespace App\Application\Ticket\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Ticket\Query\ListMyTicketsQuery;
use App\Domain\Common\Security\Authorization\UserContext;

interface ListMyTicketsUseCase
{
    public function __invoke(ListMyTicketsQuery $query, UserContext $actor): PaginatedResult;
}
