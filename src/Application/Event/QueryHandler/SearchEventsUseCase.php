<?php

declare(strict_types=1);

namespace Application\Event\QueryHandler;

use Application\Common\Result\PaginatedResult;
use Application\Event\Query\SearchEventsQuery;
use Domain\Common\Security\Authorization\UserContext;

interface SearchEventsUseCase
{
    public function __invoke(SearchEventsQuery $query, UserContext $actor): PaginatedResult;
}
