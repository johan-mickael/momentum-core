<?php

declare(strict_types=1);

namespace App\Application\Ticket\CommandHandler;

use App\Application\Common\Event\DomainEventDispatcherInterface;
use App\Application\Ticket\Command\CheckInTicketCommand;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Ticket\Exception\MarkTicketAsUsedForbidden;
use App\Domain\Ticket\Security\Authorization\TicketContext;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\ValueObject\EntryCode;

final readonly class CheckInTicketCommandHandler implements CheckInTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
    ) {}

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
