<?php

declare(strict_types=1);

namespace App\Application\Event\QueryHandler;

use App\Application\Event\Query\ListTicketCategoriesQuery;
use App\Domain\Common\Security\Authorization\UserContext;

interface ListTicketCategoriesUseCase
{
    /** @return \App\Application\Event\Result\TicketCategoryResult[] */
    public function __invoke(ListTicketCategoriesQuery $query, UserContext $actor): array;
}
