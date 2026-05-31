<?php

declare(strict_types=1);

namespace Application\Event\QueryHandler;

use Application\Common\Result\PaginatedResult;
use Application\Event\Query\ListEventsQuery;
use Domain\Common\Security\Authorization\UserContext;

interface ListEventsUseCase
{
    public function __invoke(ListEventsQuery $query, UserContext $actor): PaginatedResult;
}
