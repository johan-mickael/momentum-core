<?php

declare(strict_types=1);

namespace Application\Cart\CommandHandler;

use Application\Cart\CartResultAssembler;
use Application\Cart\Command\UpdateCartItemQuantityCommand;
use Application\Cart\Result\CartResult;
use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Cart\CartRepositoryInterface;
use Domain\Cart\Exception\CartNotFound;
use Domain\Cart\Exception\UpdateCartItemForbidden;
use Domain\Cart\Security\Authorization\CartContext;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;
use Psr\Clock\ClockInterface;

final readonly class UpdateCartItemQuantityCommandHandler implements UpdateCartItemQuantityUseCase
{
    public function __construct(
        private EventRepositoryInterface $events,
        private CartRepositoryInterface $carts,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private CartResultAssembler $assembler,
        private ClockInterface $clock,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(UpdateCartItemQuantityCommand $command, UserContext $actor): CartResult
    {
        $eventId = EventId::fromString($command->eventId);
        $categoryId = TicketCategoryId::fromString($command->categoryId);
        $now = $this->clock->now();

        return $this->transactionManager->transactional(function () use ($eventId, $categoryId, $command, $actor, $now) {
            $cart = $this->carts->findActiveByBuyerAndEventForUpdate($actor->id, $eventId)
                ?? throw CartNotFound::forBuyerAndEvent($actor->id, $eventId);

            if (!$this->authorizationService->authorize($actor, Action::UPDATE, new CartContext($cart))) {
                throw UpdateCartItemForbidden::create();
            }

            $event = $this->events->getByIdForUpdate($eventId);
            $event->categoryById($categoryId);

            $delta = $command->quantity - $cart->quantityOf($categoryId);

            if ($delta > 0) {
                for ($i = 0; $i < $delta; ++$i) {
                    $event->reserveTicketInCategory($categoryId);
                }
            } elseif ($delta < 0) {
                for ($i = 0; $i < -$delta; ++$i) {
                    $event->releaseTicketInCategory($categoryId);
                }
            }

            $cart->setItemQuantity($categoryId, $command->quantity, $now);

            $this->events->save($event);
            $this->carts->save($cart);
            $this->dispatcher->dispatch(...$cart->pullRecordedEvents());

            return $this->assembler->assemble($cart, $event);
        });
    }
}
