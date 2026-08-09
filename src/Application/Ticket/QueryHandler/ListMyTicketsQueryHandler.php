<?php

declare(strict_types=1);

namespace Application\Ticket\QueryHandler;

use Application\Common\Result\PaginatedResult;
use Application\Ticket\Query\ListMyTicketsQuery;
use Application\Ticket\Result\TicketResult;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Common\ValueObject\SortDirection;
use Domain\Ticket\Exception\ListTicketsForbidden;
use Domain\Ticket\Ticket;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\TicketStatus;

final readonly class ListMyTicketsQueryHandler implements ListMyTicketsUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private AuthorizationServiceInterface $authorizationService,
    ) {
    }

    public function __invoke(ListMyTicketsQuery $query, UserContext $actor): PaginatedResult
    {
        if (!$this->authorizationService->authorize($actor, Action::VIEW, Ticket::class)) {
            throw ListTicketsForbidden::create();
        }

        $list = $this->tickets->findByHolder(
            $actor->id,
            new PaginationCriteria($query->page, $query->limit),
            new SortCriteria($query->sort, SortDirection::fromString($query->direction)),
            null !== $query->statusFilter ? TicketStatus::tryFrom(strtolower($query->statusFilter)) : null,
        );

        return new PaginatedResult(
            data: array_map(static fn (Ticket $t) => new TicketResult(
                id: $t->id()->toString(),
                eventId: (string) $t->eventId(),
                categoryId: $t->categoryId()->toString(),
                holderId: (string) $t->holderId(),
                priceAmount: $t->price()->amount(),
                priceCurrency: $t->price()->currency()->toString(),
                status: $t->status()->value,
                reservedAt: $t->reservedAt()->format(\DateTimeInterface::ATOM),
                entryCode: $t->entryCode()->toString(),
            ), $list->items),
            total: $list->total,
            page: $list->page,
            limit: $list->limit,
        );
    }
}
