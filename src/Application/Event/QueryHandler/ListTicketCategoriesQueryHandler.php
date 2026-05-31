<?php

declare(strict_types=1);

namespace Application\Event\QueryHandler;

use Application\Event\Query\ListTicketCategoriesQuery;
use Application\Event\Result\TicketCategoryResult;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\Exception\ViewEventForbidden;
use Domain\Event\Security\Authorization\EventContext;
use Domain\Event\TicketCategory;
use Domain\Event\ValueObject\EventId;

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
