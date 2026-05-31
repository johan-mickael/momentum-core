<?php

declare(strict_types=1);

namespace Application\Venue\QueryHandler;

use Application\Venue\Query\GetVenueByIdQuery;
use Application\Venue\Result\GetVenueResult;
use Domain\Common\Security\Authorization\UserContext;

interface GetVenueByIdUseCase
{
    public function __invoke(GetVenueByIdQuery $query, UserContext $actor): GetVenueResult;
}
