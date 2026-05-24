<?php

declare(strict_types=1);

namespace App\Application\Order\QueryHandler;

use App\Application\Order\Query\ListMyOrdersQuery;
use App\Application\Order\Result\OrderResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface ListMyOrdersUseCase
{
    /** @return OrderResult[] */
    public function __invoke(ListMyOrdersQuery $query, UserContext $actor): array;
}
