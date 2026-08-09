<?php

declare(strict_types=1);

namespace Application\Cart\QueryHandler;

use Application\Cart\CartResultAssembler;
use Application\Cart\Query\ListMyCartsQuery;
use Application\Cart\Result\MyCartSummaryResult;
use Domain\Cart\Cart;
use Domain\Cart\CartRepositoryInterface;
use Domain\Cart\Exception\ListCartsForbidden;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Event\EventRepositoryInterface;
use Psr\Clock\ClockInterface;

final readonly class ListMyCartsQueryHandler implements ListMyCartsUseCase
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private EventRepositoryInterface $events,
        private AuthorizationServiceInterface $authorizationService,
        private CartResultAssembler $assembler,
        private ClockInterface $clock,
    ) {
    }

    /** @return MyCartSummaryResult[] */
    public function __invoke(ListMyCartsQuery $query, UserContext $actor): array
    {
        if (!$this->authorizationService->authorize($actor, Action::LIST, Cart::class)) {
            throw ListCartsForbidden::create();
        }

        // Removing a cart's last line leaves it ACTIVE but item-less, and a
        // lapsed TTL is only swept to EXPIRED every 30s (ExpireCartsTask) —
        // neither is a cart worth surfacing in "my carts" any more than an
        // outright empty one is.
        $now = $this->clock->now();
        $carts = array_values(array_filter(
            $this->carts->findAllActiveByBuyer($actor->id),
            static fn (Cart $cart) => [] !== $cart->items() && !$cart->isExpired($now),
        ));

        return array_map(function (Cart $cart) {
            $event = $this->events->getById($cart->eventId());

            return new MyCartSummaryResult(
                eventId: (string) $event->id(),
                eventName: $event->name(),
                cart: $this->assembler->assemble($cart, $event),
            );
        }, $carts);
    }
}
