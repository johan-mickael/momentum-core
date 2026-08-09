<?php

declare(strict_types=1);

namespace Application\Cart\QueryHandler;

use Application\Cart\Query\GetCartQuery;
use Application\Cart\Result\CartResult;
use Domain\Common\Security\Authorization\UserContext;

interface GetCartUseCase
{
    public function __invoke(GetCartQuery $query, UserContext $actor): CartResult;
}
