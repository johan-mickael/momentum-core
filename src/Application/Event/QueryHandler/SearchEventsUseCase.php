<?php

declare(strict_types=1);

namespace Application\Event\QueryHandler;

use Application\Event\Query\SearchEventsQuery;
use Application\Event\Result\SearchEventsResult;
use Domain\Common\Security\Authorization\UserContext;

interface SearchEventsUseCase
{
    public function __invoke(SearchEventsQuery $query, UserContext $actor): SearchEventsResult;
}
