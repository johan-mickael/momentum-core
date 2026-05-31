<?php

declare(strict_types=1);

namespace Application\Ticket\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Ticket\Command\CheckInTicketCommand;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Ticket\Exception\MarkTicketAsUsedForbidden;
use Domain\Ticket\Security\Authorization\TicketContext;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\ValueObject\EntryCode;

final readonly class CheckInTicketCommandHandler implements CheckInTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(CheckInTicketCommand $command, UserContext $actor): void
    {
        $ticket = $this->tickets->findByEntryCode(EntryCode::fromString($command->entryCode));

        if (!$this->authorizationService->authorize($actor, Action::UPDATE, new TicketContext($ticket))) {
            throw MarkTicketAsUsedForbidden::forTicket($ticket->id());
        }

        $ticket->markAsUsed();

        $this->tickets->save($ticket);
        $this->dispatcher->dispatch(...$ticket->pullRecordedEvents());
        $this->transactionManager->flush();
    }
}
