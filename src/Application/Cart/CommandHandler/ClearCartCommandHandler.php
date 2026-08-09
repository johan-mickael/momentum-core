<?php

declare(strict_types=1);

namespace Application\Cart\CommandHandler;

use Application\Cart\Command\ClearCartCommand;
use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Cart\CartRepositoryInterface;
use Domain\Cart\Exception\ClearCartForbidden;
use Domain\Cart\Security\Authorization\CartContext;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\EventId;
use Psr\Clock\ClockInterface;

final readonly class ClearCartCommandHandler implements ClearCartUseCase
{
    public function __construct(
        private EventRepositoryInterface $events,
        private CartRepositoryInterface $carts,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private ClockInterface $clock,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ClearCartCommand $command, UserContext $actor): void
    {
        $eventId = EventId::fromString($command->eventId);
        $now = $this->clock->now();

        $this->transactionManager->transactional(function () use ($eventId, $actor, $now) {
            $cart = $this->carts->findActiveByBuyerAndEventForUpdate($actor->id, $eventId);

            if (null === $cart) {
                return; // nothing to clear — idempotent no-op, not an error
            }

            if (!$this->authorizationService->authorize($actor, Action::DELETE, new CartContext($cart))) {
                throw ClearCartForbidden::create();
            }

            $event = $this->events->getByIdForUpdate($eventId);

            foreach ($cart->items() as $item) {
                for ($i = 0; $i < $item->quantity(); ++$i) {
                    $event->releaseTicketInCategory($item->categoryId());
                }
            }

            $cart->clear($now);

            $this->events->save($event);
            $this->carts->save($cart);
            $this->dispatcher->dispatch(...$cart->pullRecordedEvents());
        });
    }
}
