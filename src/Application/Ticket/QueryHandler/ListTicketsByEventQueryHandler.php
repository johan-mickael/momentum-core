<?php

declare(strict_types=1);

namespace App\Application\Ticket\QueryHandler;

use App\Application\Ticket\Query\ListTicketsByEventQuery;
use App\Application\Ticket\Result\TicketResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Ticket\Exception\ListTicketsForbidden;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketRepositoryInterface;
use DateTimeInterface;

final readonly class ListTicketsByEventQueryHandler implements ListTicketsByEventUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    /** @return TicketResult[] */
    public function __invoke(ListTicketsByEventQuery $query, UserContext $actor): array
    {
        if (!$this->authorizationService->authorize($actor, Action::LIST, Ticket::class)) {
            throw ListTicketsForbidden::create();
        }

        $tickets = $this->tickets->findByEvent(EventId::fromString($query->eventId));

        return array_map(
            static fn(Ticket $ticket) => new TicketResult(
                id: $ticket->id()->toString(),
                eventId: (string) $ticket->eventId(),
                categoryId: $ticket->categoryId()->toString(),
                holderId: (string) $ticket->holderId(),
                priceAmount: $ticket->price()->amount(),
                priceCurrency: $ticket->price()->currency()->toString(),
                status: $ticket->status()->value,
                reservedAt: $ticket->reservedAt()->format(DateTimeInterface::ATOM),
            ),
            $tickets,
        );
    }
}
