<?php

declare(strict_types=1);

namespace App\Application\Venue\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Venue\Query\ListVenuesQuery;
use App\Application\Venue\Result\ListVenueResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Common\ValueObject\SortDirection;
use App\Domain\Venue\Exception\ListVenuesForbidden;
use App\Domain\Venue\Venue;
use App\Domain\Venue\VenueRepositoryInterface;

final readonly class ListVenuesQueryHandler implements ListVenuesUseCase
{
    public function __construct(
        private VenueRepositoryInterface      $venueRepository,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

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
            data:  array_map(
                static fn(Venue $venue): ListVenueResult => new ListVenueResult(
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
            page:  $list->page,
            limit: $list->limit,
        );
    }
}
