<?php

declare(strict_types=1);

namespace App\Application\Venue\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Venue\Query\ListVenuesQuery;
use App\Domain\Common\Security\Authorization\UserContext;

interface ListVenuesUseCase
{
    public function __invoke(ListVenuesQuery $query, UserContext $actor): PaginatedResult;
}
