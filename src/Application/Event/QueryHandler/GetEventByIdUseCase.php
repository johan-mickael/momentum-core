<?php

declare(strict_types=1);

namespace Application\Event\QueryHandler;

use Application\Event\Query\GetEventByIdQuery;
use Application\Event\Result\EventResult;
use Domain\Common\Security\Authorization\UserContext;

interface GetEventByIdUseCase
{
    public function __invoke(GetEventByIdQuery $query, UserContext $actor): EventResult;
}
