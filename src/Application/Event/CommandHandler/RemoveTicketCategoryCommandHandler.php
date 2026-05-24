<?php

declare(strict_types=1);

namespace App\Application\Event\CommandHandler;

use App\Application\Event\Command\RemoveTicketCategoryCommand;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\Exception\RemoveTicketCategoryForbidden;
use App\Domain\Event\Security\Authorization\EventContext;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;

final readonly class RemoveTicketCategoryCommandHandler implements RemoveTicketCategoryUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private AuthorizationServiceInterface $authorizationService,
        private TransactionManagerInterface $transactionManager,
    ) {}

    public function __invoke(RemoveTicketCategoryCommand $command, UserContext $actor): void
    {
        $event = $this->eventRepository->getById(EventId::fromString($command->eventId));

        if (!$this->authorizationService->authorize($actor, Action::UPDATE, new EventContext($event))) {
            throw RemoveTicketCategoryForbidden::forEvent($event->id());
        }

        $event->removeTicketCategory(TicketCategoryId::fromString($command->categoryId));

        $this->eventRepository->save($event);
        $this->transactionManager->flush();
    }
}
