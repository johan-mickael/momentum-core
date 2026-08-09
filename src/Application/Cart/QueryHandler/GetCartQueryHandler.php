<?php

declare(strict_types=1);

namespace Application\Cart\QueryHandler;

use Application\Cart\CartResultAssembler;
use Application\Cart\Query\GetCartQuery;
use Application\Cart\Result\CartResult;
use Domain\Cart\CartRepositoryInterface;
use Domain\Cart\Exception\ViewCartForbidden;
use Domain\Cart\Security\Authorization\CartContext;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\EventId;
use Psr\Clock\ClockInterface;

final readonly class GetCartQueryHandler implements GetCartUseCase
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private EventRepositoryInterface $events,
        private AuthorizationServiceInterface $authorizationService,
        private CartResultAssembler $assembler,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(GetCartQuery $query, UserContext $actor): CartResult
    {
        $eventId = EventId::fromString($query->eventId);

        $cart = $this->carts->findActiveByBuyerAndEvent($actor->id, $eventId);

        // Removing the last line leaves the cart row ACTIVE but item-less
        // (only checkout()/expire() change status) — that's indistinguishable
        // from "no cart" to the buyer, so present it the same way. Likewise,
        // a lapsed TTL is only swept to EXPIRED every 30s (ExpireCartsTask) —
        // without this check, the buyer's own read would keep showing a
        // fully "active", editable cart for up to that long after it
        // actually died, right up until a mutation 410s on them.
        if (null === $cart || [] === $cart->items() || $cart->isExpired($this->clock->now())) {
            return CartResult::empty();
        }

        if (!$this->authorizationService->authorize($actor, Action::VIEW, new CartContext($cart))) {
            throw ViewCartForbidden::create();
        }

        $event = $this->events->getById($eventId);

        return $this->assembler->assemble($cart, $event);
    }
}
