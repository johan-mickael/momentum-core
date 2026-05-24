<?php

declare(strict_types=1);

namespace App\Application\Event\QueryHandler;

use App\Application\Event\Query\ListEventsQuery;
use App\Application\Event\Result\EventResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Event\Event;
use App\Domain\Event\EventRepositoryInterface;
use DateTimeInterface;

final readonly class ListEventsQueryHandler implements ListEventsUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    /** @return EventResult[] */
    public function __invoke(ListEventsQuery $query, UserContext $actor): array
    {
        $this->authorizationService->authorize(
            $actor,
            Action::LIST,
            Event::class,
        );

        return array_map(
            static fn(Event $event) => new EventResult(
                id: (string) $event->id(),
                name: $event->name(),
                venueId: (string) $event->venueId(),
                slug: (string) $event->slug(),
                startAt: $event->dateRange()->startAt->format(DateTimeInterface::ATOM),
                endAt: $event->dateRange()->endAt->format(DateTimeInterface::ATOM),
                status: $event->status()->value,
                createdAt: $event->createdAt()->format(DateTimeInterface::ATOM),
                description: $event->description(),
                imageUrl: $event->imageUrl(),
            ),
            $this->eventRepository->list(),
        );
    }
}
