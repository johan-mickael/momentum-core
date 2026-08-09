<?php

declare(strict_types=1);

namespace Application\Cart\CommandHandler;

use Application\Cart\CartResultAssembler;
use Application\Cart\Command\RemoveCartItemCommand;
use Application\Cart\Result\CartResult;
use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Cart\CartRepositoryInterface;
use Domain\Cart\Exception\CartNotFound;
use Domain\Cart\Exception\RemoveCartItemForbidden;
use Domain\Cart\Security\Authorization\CartContext;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;
use Psr\Clock\ClockInterface;

final readonly class RemoveCartItemCommandHandler implements RemoveCartItemUseCase
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

    public function __invoke(RemoveCartItemCommand $command, UserContext $actor): CartResult
    {
        $eventId = EventId::fromString($command->eventId);
        $categoryId = TicketCategoryId::fromString($command->categoryId);
        $now = $this->clock->now();

        return $this->transactionManager->transactional(function () use ($eventId, $categoryId, $actor, $now) {
            $cart = $this->carts->findActiveByBuyerAndEventForUpdate($actor->id, $eventId)
                ?? throw CartNotFound::forBuyerAndEvent($actor->id, $eventId);

            if (!$this->authorizationService->authorize($actor, Action::DELETE, new CartContext($cart))) {
                throw RemoveCartItemForbidden::create();
            }

            $event = $this->events->getByIdForUpdate($eventId);
            $quantityHeld = $cart->quantityOf($categoryId);

            $cart->removeItem($categoryId, $now); // throws CartItemNotFound if absent, before releasing anything

            for ($i = 0; $i < $quantityHeld; ++$i) {
                $event->releaseTicketInCategory($categoryId);
            }

            $this->events->save($event);
            $this->carts->save($cart);
            $this->dispatcher->dispatch(...$cart->pullRecordedEvents());

            return $this->assembler->assemble($cart, $event);
        });
    }
}
