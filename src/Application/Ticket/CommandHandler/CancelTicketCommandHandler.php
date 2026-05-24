<?php

declare(strict_types=1);

namespace App\Application\Ticket\CommandHandler;

use App\Application\Common\Event\DomainEventDispatcherInterface;
use App\Application\Ticket\Command\CancelTicketCommand;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Ticket\Exception\CancelTicketForbidden;
use App\Domain\Ticket\Security\Authorization\TicketContext;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\ValueObject\TicketId;

final readonly class CancelTicketCommandHandler implements CancelTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
    ) {}

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
