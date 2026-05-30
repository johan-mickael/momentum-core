<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\UpdateEventCommand;
use App\Application\Event\Result\EventResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\Exception\UpdateEventForbidden;
use App\Domain\Event\Security\Authorization\EventContext;
use App\Domain\Event\ValueObject\DateRange;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Venue\ValueObject\VenueId;
use App\Domain\Venue\VenueRepositoryInterface;

final readonly class UpdateEventCommandHandler implements UpdateEventUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private VenueRepositoryInterface $venues,
        private AuthorizationServiceInterface $authorizationService,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(UpdateEventCommand $command, UserContext $actor): EventResult
    {
        $event = $this->eventRepository->getById(EventId::fromString($command->eventId));

        if (!$this->authorizationService->authorize($actor, Action::UPDATE, new EventContext($event))) {
            throw UpdateEventForbidden::forEvent($event->id());
        }

        $this->venues->getById(VenueId::fromString($command->venueId));

        $event->updateDetails($command->name, $command->description, $command->imageUrl);
        $event->reschedule(DateRange::create($command->startAt, $command->endAt));
        $event->relocate(VenueId::fromString($command->venueId));

        $this->eventRepository->save($event);
        $this->transactionManager->flush();

        return new EventResult(
            id: (string) $event->id(),
            name: $event->name(),
            venueId: (string) $event->venueId(),
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
