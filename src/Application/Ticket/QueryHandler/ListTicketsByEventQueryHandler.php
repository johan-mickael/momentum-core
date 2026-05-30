<?php

declare(strict_types=1);

namespace App\Application\Ticket\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Ticket\Query\ListTicketsByEventQuery;
use App\Application\Ticket\Result\TicketResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Common\ValueObject\SortDirection;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Ticket\Exception\ListTicketsForbidden;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\TicketStatus;

final readonly class ListTicketsByEventQueryHandler implements ListTicketsByEventUseCase
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private AuthorizationServiceInterface $authorizationService,
    ) {
    }

    public function __invoke(ListTicketsByEventQuery $query, UserContext $actor): PaginatedResult
    {
        if (!$this->authorizationService->authorize($actor, Action::LIST, Ticket::class)) {
            throw ListTicketsForbidden::create();
        }

        $list = $this->tickets->findByEvent(
            EventId::fromString($query->eventId),
            new PaginationCriteria($query->page, $query->limit),
            new SortCriteria($query->sort, SortDirection::fromString($query->direction)),
            null !== $query->statusFilter ? TicketStatus::tryFrom($query->statusFilter) : null,
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
