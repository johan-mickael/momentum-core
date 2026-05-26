<?php

declare(strict_types=1);

namespace App\Application\Auth\QueryHandler;

use App\Application\Auth\Query\GetMeQuery;
use App\Application\Auth\Result\MeResult;

interface GetMeUseCase
{
    public function __invoke(GetMeQuery $query): MeResult;
}
