<?php

declare(strict_types=1);

namespace App\Application\Ticket\QueryHandler;

use App\Application\Ticket\Query\ListMyTicketsQuery;
use App\Application\Ticket\Result\TicketResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Ticket\Exception\ListTicketsForbidden;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketRepositoryInterface;
use DateTimeInterface;

final readonly class ListMyTicketsQueryHandler implements ListMyTicketsUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    /** @return TicketResult[] */
    public function __invoke(ListMyTicketsQuery $query, UserContext $actor): array
    {
        if (!$this->authorizationService->authorize($actor, Action::VIEW, Ticket::class)) {
            throw ListTicketsForbidden::create();
        }

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
                entryCode: $ticket->entryCode()->toString(),
            ),
            $this->tickets->findByHolder($actor->id),
        );
    }
}
