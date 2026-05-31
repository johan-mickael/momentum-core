<?php

declare(strict_types=1);

namespace Application\Order\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Order\Command\CancelOrderCommand;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Order\Exception\CancelOrderForbidden;
use Domain\Order\OrderRepositoryInterface;
use Domain\Order\Security\Authorization\OrderContext;
use Domain\Order\ValueObject\OrderId;
use Psr\Clock\ClockInterface;

final readonly class CancelOrderCommandHandler implements CancelOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CancelOrderCommand $command, UserContext $actor): void
    {
        $order = $this->orders->getById(OrderId::fromString($command->orderId));

        if (!$this->authorizationService->authorize($actor, Action::CANCEL, new OrderContext($order))) {
            throw CancelOrderForbidden::create();
        }

        $order->cancel($this->clock->now());
        $this->orders->save($order);
        $this->dispatcher->dispatch(...$order->pullRecordedEvents());
        $this->transactionManager->flush();
    }
}
