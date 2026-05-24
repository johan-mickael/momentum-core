<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\PublishEventCommand;
use App\Application\Event\Result\EventResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\Exception\PublishEventForbidden;
use App\Domain\Event\Security\Authorization\EventContext;
use App\Domain\Event\ValueObject\EventId;
use DateTimeInterface;

final readonly class PublishEventCommandHandler implements PublishEventUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private AuthorizationServiceInterface $authorizationService,
        private TransactionManagerInterface $transactionManager,
    ) {}

    public function __invoke(PublishEventCommand $command, UserContext $actor): EventResult
    {
        $event = $this->eventRepository->getById(EventId::fromString($command->eventId));

        if (!$this->authorizationService->authorize($actor, Action::UPDATE, new EventContext($event))) {
            throw PublishEventForbidden::forEvent($event->id());
        }

        $event->publish();

        $this->eventRepository->save($event);
        $this->transactionManager->flush();

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
