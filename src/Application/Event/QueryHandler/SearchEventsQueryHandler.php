<?php

declare(strict_types=1);

namespace Application\Event\QueryHandler;

use Application\Event\Query\SearchEventsQuery;
use Application\Event\Result\SearchEventsResult;
use Application\Event\Search\EventSearchCriteria;
use Application\Event\Search\EventSearchIndexInterface;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Event\Event;
use Domain\Event\Exception\ListEventsForbidden;
use Domain\Event\ValueObject\EventVisibility;

final readonly class SearchEventsQueryHandler implements SearchEventsUseCase
{
    public function __construct(
        private EventSearchIndexInterface $searchIndex,
        private AuthorizationServiceInterface $authorizationService,
    ) {
    }

    public function __invoke(SearchEventsQuery $query, UserContext $actor): SearchEventsResult
    {
        if (!$this->authorizationService->authorize($actor, Action::LIST, Event::class)) {
            throw ListEventsForbidden::create();
        }

        $page = $this->searchIndex->search(new EventSearchCriteria(
            query: $query->query,
            genres: $query->genres,
            cities: $query->cities,
            country: $query->country,
            priceMin: $query->priceMin,
            priceMax: $query->priceMax,
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
            lat: $query->lat,
            lon: $query->lon,
            radiusKm: $query->radiusKm,
            sort: $query->sort,
            direction: $query->direction,
            pagination: new PaginationCriteria($query->page, $query->limit),
            publishedOnly: EventVisibility::PUBLISHED === EventVisibility::forActor($actor),
            featuredOnly: $query->featuredOnly,
        ));

        return new SearchEventsResult(
            data: $page->items,
            facets: $page->facets,
            total: $page->total,
            page: $page->page,
            limit: $page->limit,
        );
    }
}
