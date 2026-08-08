<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\UpdateEventCommand;
use Application\Event\Result\EventResult;
use Application\Event\Result\VenueSummary;
use Application\Event\Search\EventSearchIndexer;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\EventGenre;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\Exception\UpdateEventForbidden;
use Domain\Event\Security\Authorization\EventContext;
use Domain\Event\ValueObject\DateRange;
use Domain\Event\ValueObject\EventId;
use Domain\Venue\ValueObject\VenueId;
use Domain\Venue\VenueRepositoryInterface;

final readonly class UpdateEventCommandHandler implements UpdateEventUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private VenueRepositoryInterface $venues,
        private AuthorizationServiceInterface $authorizationService,
        private TransactionManagerInterface $transactionManager,
        private EventSearchIndexer $searchIndexer,
    ) {
    }

    public function __invoke(UpdateEventCommand $command, UserContext $actor): EventResult
    {
        $event = $this->eventRepository->getById(EventId::fromString($command->eventId));

        if (!$this->authorizationService->authorize($actor, Action::UPDATE, new EventContext($event))) {
            throw UpdateEventForbidden::forEvent($event->id());
        }

        $venue = $this->venues->getById(VenueId::fromString($command->venueId));

        $event->updateDetails(
            $command->name,
            $command->description,
            $command->imageUrl,
            EventGenre::from($command->genre),
            $command->heroImageUrl,
        );
        $event->reschedule(DateRange::create($command->startAt, $command->endAt));
        $event->relocate(VenueId::fromString($command->venueId));

        $this->eventRepository->save($event);
        $this->transactionManager->flush();
        $this->searchIndexer->reindex($event->id());

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
