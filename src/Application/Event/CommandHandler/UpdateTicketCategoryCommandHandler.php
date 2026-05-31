<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\UpdateTicketCategoryCommand;
use Application\Event\Result\TicketCategoryResult;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Common\ValueObject\Currency;
use Domain\Common\ValueObject\Money;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\Exception\UpdateTicketCategoryForbidden;
use Domain\Event\Security\Authorization\EventContext;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;

final readonly class UpdateTicketCategoryCommandHandler implements UpdateTicketCategoryUseCase
{
    public function __construct(
        private EventRepositoryInterface $events,
        private AuthorizationServiceInterface $authorizationService,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(UpdateTicketCategoryCommand $command, UserContext $actor): TicketCategoryResult
    {
        $event = $this->events->getById(EventId::fromString($command->eventId));

        if (!$this->authorizationService->authorize($actor, Action::UPDATE, new EventContext($event))) {
            throw UpdateTicketCategoryForbidden::forEvent($event->id());
        }

        $category = $event->updateTicketCategory(
            id: TicketCategoryId::fromString($command->categoryId),
            name: $command->name,
            price: new Money($command->priceAmount, Currency::fromString($command->priceCurrency)),
            totalQuantity: $command->totalQuantity,
        );

        $this->events->save($event);
        $this->transactionManager->flush();

        return new TicketCategoryResult(
            id: $category->id()->toString(),
            name: $category->name(),
            priceAmount: $category->price()->amount(),
            priceCurrency: $category->price()->currency()->toString(),
            totalQuantity: $category->totalQuantity(),
            availableQuantity: $category->availableQuantity(),
        );
    }
}
