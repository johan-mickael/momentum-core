<?php

declare(strict_types=1);

namespace Application\Event\CommandHandler;

use Application\Event\Command\RemoveTicketCategoryCommand;
use Application\Event\Search\EventSearchIndexer;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\Exception\RemoveTicketCategoryForbidden;
use Domain\Event\Security\Authorization\EventContext;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;

final readonly class RemoveTicketCategoryCommandHandler implements RemoveTicketCategoryUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private AuthorizationServiceInterface $authorizationService,
        private TransactionManagerInterface $transactionManager,
        private EventSearchIndexer $searchIndexer,
    ) {
    }

    public function __invoke(RemoveTicketCategoryCommand $command, UserContext $actor): void
    {
        $event = $this->eventRepository->getById(EventId::fromString($command->eventId));

        if (!$this->authorizationService->authorize($actor, Action::UPDATE, new EventContext($event))) {
            throw RemoveTicketCategoryForbidden::forEvent($event->id());
        }

        $event->removeTicketCategory(TicketCategoryId::fromString($command->categoryId));

        $this->eventRepository->save($event);
        $this->transactionManager->flush();
        $this->searchIndexer->reindex($event->id());
    }
}
