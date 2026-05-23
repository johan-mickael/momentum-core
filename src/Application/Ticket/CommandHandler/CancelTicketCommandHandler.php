<?php

declare(strict_types=1);

namespace App\Application\Ticket\CommandHandler;

use App\Application\Ticket\Command\CancelTicketCommand;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Ticket\Exception\CancelTicketForbidden;
use App\Domain\Ticket\Security\Authorization\TicketContext;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\ValueObject\TicketId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CancelTicketCommandHandler implements CancelTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private EventRepositoryInterface $events,
        private AuthorizationServiceInterface $authorizationService,
        private EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(CancelTicketCommand $command, UserContext $actor): void
    {
        $ticket = $this->tickets->findById(TicketId::fromString($command->ticketId));

        if (!$this->authorizationService->authorize($actor, Action::DELETE, new TicketContext($ticket))) {
            throw CancelTicketForbidden::forTicket($ticket->id());
        }

        $event = $this->events->getById($ticket->eventId());

        $ticket->cancel();
        $event->releaseTicketInCategory($ticket->categoryId());

        $this->tickets->save($ticket);
        $this->events->save($event);
        $this->entityManager->flush();
    }
}
