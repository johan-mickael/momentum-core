<?php

declare(strict_types=1);

namespace App\Application\Ticket\QueryHandler;

use App\Application\Ticket\Query\ListMyTicketsQuery;
use App\Application\Ticket\Result\TicketResult;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketRepositoryInterface;
use DateTimeInterface;

final readonly class ListMyTicketsQueryHandler implements ListMyTicketsUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
    ) {}

    /** @return TicketResult[] */
    public function __invoke(ListMyTicketsQuery $query, UserContext $actor): array
    {
        $tickets = $this->tickets->findByHolder($actor->id);

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
