<?php

declare(strict_types=1);

namespace Application\Venue\QueryHandler;

use Application\Common\Result\PaginatedResult;
use Application\Venue\Query\ListVenuesQuery;
use Domain\Common\Security\Authorization\UserContext;

interface ListVenuesUseCase
{
    public function __invoke(ListVenuesQuery $query, UserContext $actor): PaginatedResult;
}
