<?php

declare(strict_types=1);

namespace Application\Order\QueryHandler;

use Application\Order\Query\GetOrderQuery;
use Application\Order\Result\OrderResult;
use Domain\Common\Security\Authorization\UserContext;

interface GetOrderUseCase
{
    public function __invoke(GetOrderQuery $query, UserContext $actor): OrderResult;
}
