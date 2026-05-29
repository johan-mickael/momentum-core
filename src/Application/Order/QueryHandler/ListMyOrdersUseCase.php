<?php

declare(strict_types=1);

namespace App\Application\Order\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Order\Query\ListMyOrdersQuery;
use App\Domain\Common\Security\Authorization\UserContext;

interface ListMyOrdersUseCase
{
    public function __invoke(ListMyOrdersQuery $query, UserContext $actor): PaginatedResult;
}
