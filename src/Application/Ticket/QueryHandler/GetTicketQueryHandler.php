<?php

declare(strict_types=1);

namespace Application\Ticket\QueryHandler;

use Application\Ticket\Query\GetTicketQuery;
use Application\Ticket\Result\TicketResult;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Ticket\Exception\ViewTicketForbidden;
use Domain\Ticket\Security\Authorization\TicketContext;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\ValueObject\TicketId;

final readonly class GetTicketQueryHandler implements GetTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private AuthorizationServiceInterface $authorizationService,
    ) {
    }

    public function __invoke(GetTicketQuery $query, UserContext $actor): TicketResult
    {
        $ticket = $this->tickets->findById(TicketId::fromString($query->ticketId));

        if (!$this->authorizationService->authorize($actor, Action::VIEW, new TicketContext($ticket))) {
            throw ViewTicketForbidden::forTicket($ticket->id());
        }

        return new TicketResult(
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
    }
}
