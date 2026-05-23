<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\AddTicketCategoryToEventCommand;
use App\Application\Event\Result\TicketCategoryResult;
use App\Domain\Common\Id\IdGeneratorInterface;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\ValueObject\Currency;
use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\Exception\AddTicketCategoryForbidden;
use App\Domain\Event\Security\Authorization\EventContext;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AddTicketCategoryToEventCommandHandler implements AddTicketCategoryToEventUseCase
{
    public function __construct(
        private EventRepositoryInterface $events,
        private IdGeneratorInterface $idGenerator,
        private AuthorizationServiceInterface $authorizationService,
        private EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(AddTicketCategoryToEventCommand $command, UserContext $actor): TicketCategoryResult
    {
        $event = $this->events->getById(EventId::fromString($command->eventId));

        if (!$this->authorizationService->authorize($actor, Action::UPDATE, new EventContext($event))) {
            throw AddTicketCategoryForbidden::forEvent($event->id());
        }

        $categoryId = TicketCategoryId::fromString($this->idGenerator->generate());
        $price = new Money($command->priceAmount, Currency::fromString($command->priceCurrency));

        $category = $event->addTicketCategory(
            id: $categoryId,
            name: $command->name,
            price: $price,
            totalQuantity: $command->totalQuantity,
        );

        $this->events->save($event);
        $this->entityManager->flush();

        return new TicketCategoryResult(
            id: $categoryId->toString(),
            name: $category->name(),
            priceAmount: $category->price()->amount(),
            priceCurrency: $category->price()->currency()->toString(),
            totalQuantity: $category->totalQuantity(),
            availableQuantity: $category->availableQuantity(),
        );
    }
}
