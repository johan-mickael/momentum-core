<?php

declare(strict_types=1);

namespace App\Application\Ticket\QueryHandler;

use App\Application\Ticket\Query\GetTicketQuery;
use App\Application\Ticket\Result\TicketResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Ticket\Exception\ViewTicketForbidden;
use App\Domain\Ticket\Security\Authorization\TicketContext;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\ValueObject\TicketId;
use DateTimeInterface;

final readonly class GetTicketQueryHandler implements GetTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

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
            reservedAt: $ticket->reservedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
