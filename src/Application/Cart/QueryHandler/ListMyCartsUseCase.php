<?php

declare(strict_types=1);

namespace Application\Cart\QueryHandler;

use Application\Cart\Query\ListMyCartsQuery;
use Domain\Common\Security\Authorization\UserContext;

interface ListMyCartsUseCase
{
    /** @return \Application\Cart\Result\MyCartSummaryResult[] */
    public function __invoke(ListMyCartsQuery $query, UserContext $actor): array;
}
