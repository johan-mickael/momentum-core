<?php

declare(strict_types=1);

namespace Application\Event\QueryHandler;

use Application\Common\Result\PaginatedResult;
use Application\Event\Query\ListEventsQuery;
use Application\Event\Result\EventResult;
use Application\Event\Result\VenueSummary;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Common\ValueObject\SortDirection;
use Domain\Event\Event;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\EventStatus;
use Domain\Event\Exception\ListEventsForbidden;
use Domain\Event\ValueObject\EventVisibility;
use Domain\Venue\VenueRepositoryInterface;

final readonly class ListEventsQueryHandler implements ListEventsUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private VenueRepositoryInterface $venueRepository,
        private AuthorizationServiceInterface $authorizationService,
    ) {
    }

    public function __invoke(ListEventsQuery $query, UserContext $actor): PaginatedResult
    {
        if (!$this->authorizationService->authorize($actor, Action::LIST, Event::class)) {
            throw ListEventsForbidden::create();
        }

        $list = $this->eventRepository->list(
            EventVisibility::forActor($actor),
            new PaginationCriteria($query->page, $query->limit),
            new SortCriteria($query->sort, SortDirection::fromString($query->direction)),
            null !== $query->statusFilter ? EventStatus::tryFrom($query->statusFilter) : null,
        );

        return new PaginatedResult(
            data: array_map(fn (Event $event) => $this->toResult($event), $list->items),
            total: $list->total,
            page: $list->page,
            limit: $list->limit,
        );
    }

    private function toResult(Event $event): EventResult
    {
        $venue = $this->venueRepository->getById($event->venueId());
        $location = $venue->location();

        return new EventResult(
            id: (string) $event->id(),
            name: $event->name(),
            venue: new VenueSummary(
                id: (string) $venue->id(),
                name: $venue->name(),
                street: $venue->address()->street(),
                city: $venue->address()->city(),
                zipCode: $venue->address()->zipCode(),
                country: $venue->address()->country(),
                latitude: $location->latitude(),
                longitude: $location->longitude(),
            ),
            slug: (string) $event->slug(),
            startAt: $event->dateRange()->startAt->format(\DateTimeInterface::ATOM),
            endAt: $event->dateRange()->endAt->format(\DateTimeInterface::ATOM),
            status: $event->status()->value,
            createdAt: $event->createdAt()->format(\DateTimeInterface::ATOM),
            description: $event->description(),
            imageUrl: $event->imageUrl(),
        );
    }
}
