<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Event\Command\PublishEventCommand;
use Application\Event\Result\EventResult;
use Application\Event\Result\VenueSummary;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\Exception\PublishEventForbidden;
use Domain\Event\Security\Authorization\EventContext;
use Domain\Event\ValueObject\EventId;
use Domain\Venue\VenueRepositoryInterface;

final readonly class PublishEventCommandHandler implements PublishEventUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private VenueRepositoryInterface $venues,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(PublishEventCommand $command, UserContext $actor): EventResult
    {
        $event = $this->eventRepository->getById(EventId::fromString($command->eventId));

        if (!$this->authorizationService->authorize($actor, Action::UPDATE, new EventContext($event))) {
            throw PublishEventForbidden::forEvent($event->id());
        }

        $event->publish();

        $this->eventRepository->save($event);
        $this->dispatcher->dispatch(...$event->pullRecordedEvents());
        $this->transactionManager->flush();

        $venue = $this->venues->getById($event->venueId());

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
                latitude: $venue->location()->latitude(),
                longitude: $venue->location()->longitude(),
            ),
            slug: (string) $event->slug(),
            startAt: $event->dateRange()->startAt->format(\DateTimeInterface::ATOM),
            endAt: $event->dateRange()->endAt->format(\DateTimeInterface::ATOM),
            status: $event->status()->value,
            createdAt: $event->createdAt()->format(\DateTimeInterface::ATOM),
            description: $event->description(),
            imageUrl: $event->imageUrl(),
            heroImageUrl: $event->heroImageUrl(),
            genre: $event->genre()->value,
        );
    }
}
