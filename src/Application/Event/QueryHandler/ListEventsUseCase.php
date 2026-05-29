<?php

declare(strict_types=1);

namespace App\Application\Event\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Event\Query\ListEventsQuery;
use App\Domain\Common\Security\Authorization\UserContext;

interface ListEventsUseCase
{
    public function __invoke(ListEventsQuery $query, UserContext $actor): PaginatedResult;
}
