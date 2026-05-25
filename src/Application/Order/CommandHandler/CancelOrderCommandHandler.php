<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Application\Common\Event\DomainEventDispatcherInterface;
use App\Application\Order\Command\CancelOrderCommand;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Order\Exception\CancelOrderForbidden;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\Security\Authorization\OrderContext;
use App\Domain\Order\ValueObject\OrderId;
use Psr\Clock\ClockInterface;

final readonly class CancelOrderCommandHandler implements CancelOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {}

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
