<?php

declare(strict_types=1);

namespace Application\Ticket\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Ticket\Command\ConfirmTicketCommand;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Ticket\Exception\ConfirmTicketForbidden;
use Domain\Ticket\Security\Authorization\TicketContext;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\ValueObject\TicketId;

final readonly class ConfirmTicketCommandHandler implements ConfirmTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ConfirmTicketCommand $command, UserContext $actor): void
    {
        $ticket = $this->tickets->findById(TicketId::fromString($command->ticketId));

        if (!$this->authorizationService->authorize($actor, Action::UPDATE, new TicketContext($ticket))) {
            throw ConfirmTicketForbidden::forTicket($ticket->id());
        }

        $ticket->confirm();

        $this->tickets->save($ticket);
        $this->dispatcher->dispatch(...$ticket->pullRecordedEvents());
        $this->transactionManager->flush();
    }
}
