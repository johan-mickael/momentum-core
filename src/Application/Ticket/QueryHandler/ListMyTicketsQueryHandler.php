<?php

declare(strict_types=1);

namespace App\Application\Ticket\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Ticket\Query\ListMyTicketsQuery;
use App\Application\Ticket\Result\TicketResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Ticket\Exception\ListTicketsForbidden;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\TicketStatus;
use DateTimeInterface;

final readonly class ListMyTicketsQueryHandler implements ListMyTicketsUseCase
{
    public function __construct(
        private TicketRepositoryInterface     $tickets,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function __invoke(ListMyTicketsQuery $query, UserContext $actor): PaginatedResult
    {
        if (!$this->authorizationService->authorize($actor, Action::VIEW, Ticket::class)) {
            throw ListTicketsForbidden::create();
        }

        $statusFilter = $query->statusFilter !== null
            ? TicketStatus::tryFrom($query->statusFilter)
            : null;

        $list = $this->tickets->findByHolder(
            $actor->id,
            $query->pagination,
            $query->sort,
            $statusFilter,
        );

        return new PaginatedResult(
            data:  array_map(static fn(Ticket $t) => new TicketResult(
                id:            $t->id()->toString(),
                eventId:       (string) $t->eventId(),
                categoryId:    $t->categoryId()->toString(),
                holderId:      (string) $t->holderId(),
                priceAmount:   $t->price()->amount(),
                priceCurrency: $t->price()->currency()->toString(),
                status:        $t->status()->value,
                reservedAt:    $t->reservedAt()->format(DateTimeInterface::ATOM),
                entryCode:     $t->entryCode()->toString(),
            ), $list->items),
            total: $list->total,
            page:  $list->page,
            limit: $list->limit,
        );
    }
}
