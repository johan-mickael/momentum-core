<?php

declare(strict_types=1);

namespace Application\Ticket\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Ticket\Command\ReserveTicketCommand;
use Application\Ticket\Result\ReserveTicketResult;
use Domain\Common\Id\IdGeneratorInterface;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;
use Domain\Ticket\Exception\ReserveTicketForbidden;
use Domain\Ticket\Ticket;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\ValueObject\EntryCode;
use Domain\Ticket\ValueObject\TicketId;
use Psr\Clock\ClockInterface;

final readonly class ReserveTicketCommandHandler implements ReserveTicketUseCase
{
    public function __construct(
        private EventRepositoryInterface $events,
        private TicketRepositoryInterface $tickets,
        private DomainEventDispatcherInterface $dispatcher,
        private AuthorizationServiceInterface $authorizationService,
        private IdGeneratorInterface $idGenerator,
        private ClockInterface $clock,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ReserveTicketCommand $command, UserContext $actor): ReserveTicketResult
    {
        if (!$this->authorizationService->authorize($actor, Action::CREATE, Ticket::class)) {
            throw ReserveTicketForbidden::create();
        }

        $eventId = EventId::fromString($command->eventId);
        $categoryId = TicketCategoryId::fromString($command->categoryId);

        return $this->transactionManager->transactional(function () use ($eventId, $categoryId, $actor) {
            $event = $this->events->getByIdForUpdate($eventId);

            $category = $event->categoryById($categoryId);

            $event->reserveTicketInCategory($categoryId);

            $ticket = Ticket::reserve(
                id: TicketId::fromString($this->idGenerator->generate()),
                eventId: $eventId,
                categoryId: $categoryId,
                holderId: $actor->id,
                price: $category->price(),
                reservedAt: $this->clock->now(),
                entryCode: EntryCode::fromString($this->idGenerator->generate()),
            );

            $this->events->save($event);
            $this->tickets->save($ticket);
            $this->dispatcher->dispatch(...$ticket->pullRecordedEvents());

            return new ReserveTicketResult(
                id: $ticket->id()->toString(),
                eventId: (string) $ticket->eventId(),
                categoryId: $ticket->categoryId()->toString(),
                holderId: (string) $ticket->holderId(),
                priceAmount: $ticket->price()->amount(),
                priceCurrency: $ticket->price()->currency()->toString(),
                status: $ticket->status()->value,
                reservedAt: $ticket->reservedAt()->format(\DateTimeInterface::ATOM),
                entryCode: $ticket->entryCode()->toString(),
            );
        });
    }
}
