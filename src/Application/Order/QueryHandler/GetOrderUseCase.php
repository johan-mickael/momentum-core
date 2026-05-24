<?php

declare(strict_types=1);

namespace App\Application\Order\QueryHandler;

use App\Application\Order\Query\GetOrderQuery;
use App\Application\Order\Result\OrderResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface GetOrderUseCase
{
    public function __invoke(GetOrderQuery $query, UserContext $actor): OrderResult;
}
