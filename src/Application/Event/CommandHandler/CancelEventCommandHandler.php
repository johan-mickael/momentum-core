<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Common\Event\DomainEventDispatcherInterface;
use App\Application\Event\Command\CancelEventCommand;
use App\Application\Event\Result\EventResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\Exception\CancelEventForbidden;
use App\Domain\Event\Security\Authorization\EventContext;
use App\Domain\Event\ValueObject\EventId;
use DateTimeInterface;

final readonly class CancelEventCommandHandler implements CancelEventUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
    ) {}

    public function __invoke(CancelEventCommand $command, UserContext $actor): EventResult
    {
        $event = $this->eventRepository->getById(EventId::fromString($command->eventId));

        if (!$this->authorizationService->authorize($actor, Action::UPDATE, new EventContext($event))) {
            throw CancelEventForbidden::forEvent($event->id());
        }

        $event->cancel();

        $this->eventRepository->save($event);
        $this->dispatcher->dispatch(...$event->pullRecordedEvents());
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
