<?php

declare(strict_types=1);

namespace App\Application\Venue\QueryHandler;

use App\Application\Venue\Query\GetVenueByIdQuery;
use App\Application\Venue\Result\GetVenueResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface GetVenueByIdUseCase
{
    public function __invoke(GetVenueByIdQuery $query, UserContext $actor): GetVenueResult;
}
