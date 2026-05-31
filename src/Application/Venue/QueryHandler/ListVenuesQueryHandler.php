<?php

declare(strict_types=1);

namespace Application\Venue\QueryHandler;

use Application\Common\Result\PaginatedResult;
use Application\Venue\Query\ListVenuesQuery;
use Application\Venue\Result\ListVenueResult;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Common\ValueObject\SortDirection;
use Domain\Venue\Exception\ListVenuesForbidden;
use Domain\Venue\Venue;
use Domain\Venue\VenueRepositoryInterface;

final readonly class ListVenuesQueryHandler implements ListVenuesUseCase
{
    public function __construct(
        private VenueRepositoryInterface $venueRepository,
        private AuthorizationServiceInterface $authorizationService,
    ) {
    }

    public function __invoke(ListVenuesQuery $query, UserContext $actor): PaginatedResult
    {
        if (!$this->authorizationService->authorize($actor, Action::LIST, Venue::class)) {
            throw new ListVenuesForbidden();
        }

        $list = $this->venueRepository->listVenues(
            new PaginationCriteria($query->page, $query->limit),
            new SortCriteria($query->sort, SortDirection::fromString($query->direction)),
            $query->cityFilter,
            $query->countryFilter,
        );

        return new PaginatedResult(
            data: array_map(
                static fn (Venue $venue): ListVenueResult => new ListVenueResult(
                    (string) $venue->id(),
                    $venue->name(),
                    $venue->address()->street(),
                    $venue->address()->city(),
                    $venue->address()->country(),
                    $venue->address()->zipCode(),
                    $venue->capacity()->value,
                    $venue->location()->latitude(),
                    $venue->location()->longitude(),
                ),
                $list->items,
            ),
            total: $list->total,
            page: $list->page,
            limit: $list->limit,
        );
    }
}
