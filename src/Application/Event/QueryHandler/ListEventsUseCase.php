<?php

declare(strict_types=1);

namespace App\Application\Event\QueryHandler;

use App\Application\Event\Query\ListEventsQuery;
use App\Application\Event\Result\EventResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface ListEventsUseCase
{
    /** @return EventResult[] */
    public function __invoke(ListEventsQuery $query, UserContext $actor): array;
}
