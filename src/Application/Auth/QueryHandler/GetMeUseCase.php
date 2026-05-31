<?php

declare(strict_types=1);

namespace Application\Auth\QueryHandler;

use Application\Auth\Query\GetMeQuery;
use Application\Auth\Result\MeResult;

interface GetMeUseCase
{
    public function __invoke(GetMeQuery $query): MeResult;
}
