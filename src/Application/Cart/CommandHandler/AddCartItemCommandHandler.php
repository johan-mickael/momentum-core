<?php

declare(strict_types=1);

namespace Application\Cart\CommandHandler;

use Application\Cart\CartResultAssembler;
use Application\Cart\Command\AddCartItemCommand;
use Application\Cart\Result\CartResult;
use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Cart\Cart;
use Domain\Cart\CartRepositoryInterface;
use Domain\Cart\Exception\AddCartItemForbidden;
use Domain\Cart\ValueObject\CartId;
use Domain\Common\Id\IdGeneratorInterface;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;
use Psr\Clock\ClockInterface;

final readonly class AddCartItemCommandHandler implements AddCartItemUseCase
{
    public function __construct(
        private EventRepositoryInterface $events,
        private CartRepositoryInterface $carts,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private CartResultAssembler $assembler,
        private IdGeneratorInterface $idGenerator,
        private ClockInterface $clock,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(AddCartItemCommand $command, UserContext $actor): CartResult
    {
        if (!$this->authorizationService->authorize($actor, Action::CREATE, Cart::class)) {
            throw AddCartItemForbidden::create();
        }

        $eventId = EventId::fromString($command->eventId);
        $categoryId = TicketCategoryId::fromString($command->categoryId);
        $now = $this->clock->now();

        return $this->transactionManager->transactional(function () use ($eventId, $categoryId, $command, $actor, $now) {
            // Locking the event BEFORE looking up the cart is load-bearing:
            // it serializes two concurrent "first add" requests for the
            // same (buyer, event) on this row lock, so the second request's
            // findActiveByBuyerAndEventForUpdate() only runs after the
            // first has committed its new cart — preventing a duplicate
            // active-cart insert race without needing to catch a unique
            // constraint violation here.
            $event = $this->events->getByIdForUpdate($eventId);
            $event->categoryById($categoryId); // fail fast on an unknown category before touching any holds

            $existingCart = $this->carts->findActiveByBuyerAndEventForUpdate($actor->id, $eventId);

            if (null !== $existingCart && $existingCart->isExpired($now)) {
                // The periodic sweep (ExpireCartsTask) runs at most every
                // 30s, so a lapsed cart can still be sitting here as
                // status=ACTIVE. "Add to cart" must always work — do the
                // sweep's job inline (release + expire) instead of failing
                // against a cart the buyer has no way to see or clear
                // themselves, then fall through to opening a fresh one.
                foreach ($existingCart->items() as $item) {
                    for ($i = 0; $i < $item->quantity(); ++$i) {
                        $event->releaseTicketInCategory($item->categoryId());
                    }
                }
                $existingCart->expire($now);
                $this->carts->save($existingCart);
                // Doctrine's flush() always runs every pending INSERT before
                // any UPDATE, regardless of call order — without flushing
                // here, the new cart's INSERT would race ahead of this
                // UPDATE and hit uniq_cart_active_buyer_event against the
                // stale row, which (from the DB's perspective) is still
                // ACTIVE. Flushing now commits the expiry within the
                // transaction before the new cart is even scheduled.
                $this->transactionManager->flush();
                $this->dispatcher->dispatch(...$existingCart->pullRecordedEvents());
                $existingCart = null;
            }

            $cart = $existingCart
                ?? Cart::open(CartId::fromString($this->idGenerator->generate()), $actor->id, $eventId, $now);

            for ($i = 0; $i < $command->quantity; ++$i) {
                $event->reserveTicketInCategory($categoryId);
            }

            $cart->addItem($categoryId, $command->quantity, $now);

            $this->events->save($event);
            $this->carts->save($cart);
            $this->dispatcher->dispatch(...$cart->pullRecordedEvents());

            return $this->assembler->assemble($cart, $event);
        });
    }
}
