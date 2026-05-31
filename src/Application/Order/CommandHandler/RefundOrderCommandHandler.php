<?php

declare(strict_types=1);

namespace Application\Order\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Order\Command\RefundOrderCommand;
use Application\Order\Exception\OrderHasNoPaymentReference;
use Application\Order\Service\RefundPaymentServiceInterface;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Order\Exception\CancelOrderForbidden;
use Domain\Order\OrderRepositoryInterface;
use Domain\Order\Security\Authorization\OrderContext;
use Domain\Order\ValueObject\OrderId;
use Psr\Clock\ClockInterface;

final readonly class RefundOrderCommandHandler implements RefundOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private RefundPaymentServiceInterface $refundService,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RefundOrderCommand $command, UserContext $actor): void
    {
        $order = $this->orders->getById(OrderId::fromString($command->orderId));

        if (!$this->authorizationService->authorize($actor, Action::CANCEL, new OrderContext($order))) {
            throw CancelOrderForbidden::create();
        }

        $paymentReference = $order->paymentReference();

        if (null === $paymentReference) {
            throw OrderHasNoPaymentReference::forOrder($order->id());
        }

        $this->refundService->refund($paymentReference);

        $order->refund($this->clock->now());
        $this->orders->save($order);
        $this->dispatcher->dispatch(...$order->pullRecordedEvents());
        $this->transactionManager->flush();
    }
}
