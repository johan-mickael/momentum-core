<?php

declare(strict_types=1);

namespace Application\Event\QueryHandler;

use Application\Event\Query\ListTicketCategoriesQuery;
use Domain\Common\Security\Authorization\UserContext;

interface ListTicketCategoriesUseCase
{
    /** @return \Application\Event\Result\TicketCategoryResult[] */
    public function __invoke(ListTicketCategoriesQuery $query, UserContext $actor): array;
}
