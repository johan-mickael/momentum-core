<?php

declare(strict_types=1);

namespace App\Application\Event\QueryHandler;

use App\Application\Event\Query\GetEventByIdQuery;
use App\Application\Event\Result\EventResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface GetEventByIdUseCase
{
    public function __invoke(GetEventByIdQuery $query, UserContext $actor): EventResult;
}
