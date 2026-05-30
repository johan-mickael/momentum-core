<?php

declare(strict_types=1);

namespace App\Application\Event\QueryHandler;

use App\Application\Event\Query\ListTicketCategoriesQuery;
use App\Application\Event\Result\TicketCategoryResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\Exception\ViewEventForbidden;
use App\Domain\Event\Security\Authorization\EventContext;
use App\Domain\Event\TicketCategory;
use App\Domain\Event\ValueObject\EventId;

final readonly class ListTicketCategoriesQueryHandler implements ListTicketCategoriesUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private AuthorizationServiceInterface $authorizationService,
    ) {
    }

    /** @return TicketCategoryResult[] */
    public function __invoke(ListTicketCategoriesQuery $query, UserContext $actor): array
    {
        $event = $this->eventRepository->getById(EventId::fromString($query->eventId));

        if (!$this->authorizationService->authorize($actor, Action::VIEW, new EventContext($event))) {
            throw ViewEventForbidden::forEvent($event->id());
        }

        return array_map(
            static fn (TicketCategory $category) => new TicketCategoryResult(
                id: $category->id()->toString(),
                name: $category->name(),
                priceAmount: $category->price()->amount(),
                priceCurrency: $category->price()->currency()->toString(),
                totalQuantity: $category->totalQuantity(),
                availableQuantity: $category->availableQuantity(),
            ),
            $event->ticketCategories(),
        );
    }
}
