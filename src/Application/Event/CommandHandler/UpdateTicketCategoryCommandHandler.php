<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\UpdateTicketCategoryCommand;
use App\Application\Event\Result\TicketCategoryResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Common\ValueObject\Currency;
use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\Exception\UpdateTicketCategoryForbidden;
use App\Domain\Event\Security\Authorization\EventContext;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;

final readonly class UpdateTicketCategoryCommandHandler implements UpdateTicketCategoryUseCase
{
    public function __construct(
        private EventRepositoryInterface $events,
        private AuthorizationServiceInterface $authorizationService,
        private TransactionManagerInterface $transactionManager,
    ) {}

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
