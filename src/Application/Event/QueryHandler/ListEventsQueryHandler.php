<?php

declare(strict_types=1);

namespace App\Application\Event\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Event\Query\ListEventsQuery;
use App\Application\Event\Result\EventResult;
use App\Application\Event\Result\VenueSummary;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Common\ValueObject\SortDirection;
use App\Domain\Event\Event;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\EventStatus;
use App\Domain\Event\Exception\ListEventsForbidden;
use App\Domain\Event\ValueObject\EventVisibility;
use App\Domain\Venue\VenueRepositoryInterface;

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
                latitude: $location?->latitude(),
                longitude: $location?->longitude(),
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
