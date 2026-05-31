<?php

declare(strict_types=1);

namespace Application\Order\QueryHandler;

use Application\Common\Result\PaginatedResult;
use Application\Order\Query\ListMyOrdersQuery;
use Domain\Common\Security\Authorization\UserContext;

interface ListMyOrdersUseCase
{
    public function __invoke(ListMyOrdersQuery $query, UserContext $actor): PaginatedResult;
}
