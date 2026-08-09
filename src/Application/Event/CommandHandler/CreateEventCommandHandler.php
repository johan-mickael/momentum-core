<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Event\Command\CreateEventCommand;
use Application\Event\Result\CreateEventResult;
use Domain\Common\Id\IdGeneratorInterface;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\Event;
use Domain\Event\EventGenre;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\Exception\CreateEventForbidden;
use Domain\Event\ValueObject\DateRange;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\EventSlug;
use Domain\Venue\ValueObject\VenueId;
use Domain\Venue\VenueRepositoryInterface;
use Psr\Clock\ClockInterface;

readonly class CreateEventCommandHandler implements CreateEventUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private VenueRepositoryInterface $venueRepository,
        private IdGeneratorInterface $idGenerator,
        private ClockInterface $clock,
        private TransactionManagerInterface $transactionManager,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(CreateEventCommand $command, UserContext $actor): CreateEventResult
    {
        if (!$this->authorizationService->authorize(
            $actor,
            Action::CREATE,
            Event::class,
        )) {
            throw new CreateEventForbidden();
        }

        // Check for venue if exists
        $this->venueRepository->getById(VenueId::fromString($command->venueId));

        $event = Event::create(
            id: EventId::fromString($this->idGenerator->generate()),
            name: $command->name,
            venueId: VenueId::fromString($command->venueId),
            slug: EventSlug::create($command->eventSlug),
            dateRange: DateRange::create(
                $command->startAt,
                $command->endAt,
            ),
            createdAt: $this->clock->now(),
            description: $command->description,
            imageUrl: $command->imageUrl,
            heroImageUrl: $command->heroImageUrl,
            genre: EventGenre::from($command->genre),
        );

        $this->eventRepository->create($event);
        $this->dispatcher->dispatch(...$event->pullRecordedEvents());
        $this->transactionManager->flush();

        return new CreateEventResult(
            (string) $event->id(),
            $event->name(),
            (string) $event->venueId(),
            (string) $event->slug(),
            $event->dateRange()->startAt->format(\DateTimeInterface::ATOM),
            $event->dateRange()->endAt->format(\DateTimeInterface::ATOM),
            $event->status()->value,
            $event->createdAt()->format(\DateTimeInterface::ATOM),
            $event->description(),
            $event->imageUrl(),
            $event->heroImageUrl(),
            $event->genre()->value,
        );
    }
}
