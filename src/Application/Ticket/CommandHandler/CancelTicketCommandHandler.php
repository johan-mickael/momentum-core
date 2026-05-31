<?php

declare(strict_types=1);

namespace Application\Ticket\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Ticket\Command\CancelTicketCommand;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Ticket\Exception\CancelTicketForbidden;
use Domain\Ticket\Security\Authorization\TicketContext;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\ValueObject\TicketId;

final readonly class CancelTicketCommandHandler implements CancelTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(CancelTicketCommand $command, UserContext $actor): void
    {
        $ticket = $this->tickets->findById(TicketId::fromString($command->ticketId));

        if (!$this->authorizationService->authorize($actor, Action::DELETE, new TicketContext($ticket))) {
            throw CancelTicketForbidden::forTicket($ticket->id());
        }

        $ticket->cancel();
        $this->tickets->save($ticket);
        $this->dispatcher->dispatch(...$ticket->pullRecordedEvents());
        $this->transactionManager->flush();
    }
}
