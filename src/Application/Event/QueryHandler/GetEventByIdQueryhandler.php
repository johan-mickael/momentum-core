<?php

declare(strict_types=1);

namespace App\Application\Event\QueryHandler;

use App\Application\Event\Query\GetEventByIdQuery;
use App\Application\Event\Result\EventResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\Security\Authorization\EventContext;
use App\Domain\Event\ValueObject\EventId;
use DateTimeInterface;

final readonly class GetEventByIdQueryhandler implements GetEventByIdUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function __invoke(GetEventByIdQuery $query, UserContext $actor): EventResult
    {
        $event = $this->eventRepository->getById(EventId::fromString($query->eventId));

        $this->authorizationService->authorize(
            $actor,
            Action::VIEW,
            new EventContext($event),
        );

        return new EventResult(
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
        );
    }
}
